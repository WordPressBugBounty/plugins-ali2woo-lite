<?php
use AliNext_Lite\AbstractController;

/**
 * @var string $sort_query
 * @var array|null $paginator
 */
?>
<?php if (!empty($paginator['total_pages']) && $paginator['total_pages'] > 1): ?>
    <div class="pagination">
        <div class="pagination__wrapper">
            <ul class="pagination__list">
                <?php
                $cur = (int) $paginator['cur_page'];
                $total = (int) $paginator['total_pages'];
                $base = 'admin.php?page=a2wl_import&o=' . urlencode($sort_query);
                $alinext_lite_nonce = wp_create_nonce(AbstractController::PAGE_NONCE_ACTION);

                // Previous link
                $prev_disabled = $cur <= 1 ? ' class="disabled"' : '';
                $prev_page = max(1, $cur - 1);
                ?>
                <li<?php echo $prev_disabled; ?>>
                    <a href="<?php echo esc_url(admin_url($base . '&cur_page=' . $prev_page . '&ali2woo_nonce=' . $alinext_lite_nonce)); ?>">«</a>
                </li>

                <?php foreach ((array) $paginator['pages_list'] as $p): ?>
                    <?php if ($p): ?>
                        <?php if ($p == $cur): ?>
                            <li class="active"><span><?php echo (int) $p; ?></span></li>
                        <?php else: ?>
                            <li>
                                <a href="<?php echo esc_url(admin_url($base . '&cur_page=' . (int) $p  . '&ali2woo_nonce=' . $alinext_lite_nonce)); ?>">
                                    <?php echo (int) $p; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="disabled"><span>…</span></li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php
                // Next link
                $next_disabled = $cur >= $total ? ' class="disabled"' : '';
                $next_page = min($total, $cur + 1);
                ?>
                <li<?php echo $next_disabled; ?>>
                    <a href="<?php echo esc_url(admin_url($base . '&cur_page=' . $next_page  . '&ali2woo_nonce=' . $alinext_lite_nonce)); ?>">»</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>
