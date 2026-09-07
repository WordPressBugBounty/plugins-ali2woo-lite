<?php

/**
 * Migrates AliExpress image URLs from the old alicdn.com domain to the new
 * aliexpress-media.com domain in imported product data and WooCommerce content.
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class MigrateAlicdnUrlProcess extends BaseJob
{
    public const ACTION_CODE = 'a2wl_migrate_alicdn_url_process';

    private const NEW_HOST = 'ae-pic-a1.aliexpress-media.com';

    protected $action = self::ACTION_CODE;
    protected string $title = 'Migrate AliExpress image URL domain';

    private const PAGE_SIZE = 500;

    protected ProductImport $ProductImport;

    public function __construct(ProductImport $ProductImport)
    {
        parent::__construct();

        $this->ProductImport = $ProductImport;
    }

    /**
     * @return self
     */
    public function pushToQueue(): self
    {
        if ($this->isQueued() || $this->is_processing()) {
            a2wl_info_log(
                '[AlicdnUrlMigrate] Migration already queued or running, skipping double run.'
            );
            return $this;
        }

        $importedCount = 0;
        foreach ($this->ProductImport->get_product_id_list() as $externalId) {
            $product = $this->ProductImport->get_product($externalId);
            if (!$product) {
                continue;
            }

            if (str_contains(serialize($product), '.alicdn.com')) {
                $this->push_to_queue([
                    'type' => 'imported',
                    'externalId' => $externalId,
                ]);
                $importedCount++;
            }
        }

        $postCount = 0;
        global $wpdb;
        $offset = 0;
        while (true) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT ID FROM {$wpdb->posts} " .
                        "WHERE (post_type = 'attachment' AND guid LIKE %s) " .
                        "   OR (post_type = 'product' AND post_content LIKE %s) " .
                        "ORDER BY ID LIMIT %d OFFSET %d",
                    '%' . 'alicdn.com' . '%',
                    '%' . 'alicdn.com' . '%',
                    self::PAGE_SIZE,
                    $offset
                ),
                ARRAY_A
            );

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $this->push_to_queue([
                    'type' => 'post',
                    'postId' => (int) $row['ID'],
                ]);
                $postCount++;
            }

            $offset += self::PAGE_SIZE;
        }

        $this->save();

        a2wl_info_log(sprintf(
            '[AlicdnUrlMigrate] Queued job "%s": %d imported product(s), %d post(s).',
            $this->getTitle(),
            $importedCount,
            $postCount
        ));

        return $this;
    }

    protected function task($item): bool
    {
        $type = $item['type'] ?? null;

        if ($type === 'imported') {
            $this->migrateImportedProduct((string) ($item['externalId'] ?? ''));
        } elseif ($type === 'post') {
            $this->migratePost((int) ($item['postId'] ?? 0));
        }

        return false;
    }

    private function migrateImportedProduct(string $externalId): void
    {
        if ($externalId === '') {
            return;
        }

        $product = $this->ProductImport->get_product($externalId);
        if (!$product) {
            return;
        }

        if (!str_contains(serialize($product), '.alicdn.com')) {
            return;
        }

        $updated = $this->replaceInArray($product);
        $this->ProductImport->save_product($externalId, $updated);

        a2wl_info_log(sprintf(
            '[AlicdnUrlMigrate] Imported product #%s: image URLs updated to %s.',
            $externalId,
            self::NEW_HOST
        ));
    }

    private function migratePost(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }

        $post = get_post($postId);
        if (!$post) {
            return;
        }

        if ($post->post_type === 'attachment') {
            $this->migrateAttachment($post);
            return;
        }

        if ($post->post_type === 'product' && str_contains($post->post_content, '.alicdn.com')) {
            $newContent = $this->replaceUrl($post->post_content);
            if ($newContent !== $post->post_content) {
                wp_update_post(['ID' => $postId, 'post_content' => $newContent]);
                a2wl_info_log(sprintf(
                    '[AlicdnUrlMigrate] Product #%d: post_content URLs updated to %s.',
                    $postId,
                    self::NEW_HOST
                ));
            }
        }
    }

    private function migrateAttachment(\WP_Post $post): void
    {
        global $wpdb;

        $changed = false;

        $guid = $post->guid;
        if (str_contains($guid, '.alicdn.com')) {
            $newGuid = $this->replaceUrl($guid);
            if ($newGuid !== $guid) {
                $wpdb->update(
                    $wpdb->posts,
                    ['guid' => $newGuid],
                    ['ID' => $post->ID]
                );
                $changed = true;
            }
        }

        foreach (['_wp_attached_file', '_a2w_external_image_url'] as $metaKey) {
            $value = get_post_meta($post->ID, $metaKey, true);
            if (!is_string($value) || !str_contains($value, '.alicdn.com')) {
                continue;
            }

            $newValue = $this->replaceUrl($value);
            if ($newValue !== $value) {
                update_post_meta($post->ID, $metaKey, $newValue);
                $changed = true;
            }
        }

        if ($changed) {
            a2wl_info_log(sprintf(
                '[AlicdnUrlMigrate] Attachment #%d: URLs updated to %s.',
                $post->ID,
                self::NEW_HOST
            ));
        }
    }

    /**
     * Replace old alicdn.com hosts with the new aliexpress-media.com host.
     */
    private function replaceUrl(string $url): string
    {
        return (string) preg_replace(
            '/\bae0[1-5]\.alicdn\.com\b/i',
            self::NEW_HOST,
            $url
        );
    }

    private function replaceInArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if (str_contains($value, '.alicdn.com')) {
                    $data[$key] = $this->replaceUrl($value);
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->replaceInArray($value);
            }
        }

        return $data;
    }
}
