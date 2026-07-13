<?php

use AliNext_Lite\AbstractController;
use AliNext_Lite\TipOfDay;
use AliNext_Lite\TipOfDayAjaxController;

/**
 * @var TipOfDay $TipOfDay
 */
$hasCta = $TipOfDay->getCtaUrl();
$closeLabel = __('Close', 'ali2woo');
?>
<div class="a2wl-tip-of-day-overlay opened"
     data-id="<?php echo esc_attr($TipOfDay->id); ?>"
>
    <div class="a2wl-tip-of-day">
        <div class="a2wl-tip-of-day__header">
            <h3 class="a2wl-tip-of-day__title">
                <?php echo esc_html($TipOfDay->getName()); ?>
            </h3>
            <button class="a2wl-tip-of-day__close" type="button">&times;</button>
        </div>
        <div class="a2wl-tip-of-day__body">
            <?php
            $allowedHtml = [
                'a' => [
                    'href' => [],
                    'title' => [],
                    'target' => [],
                ],
                'br' => [],
                'em' => [],
                'strong' => [],
                'p' => [],
                'ul' => [],
                'ol' => [],
                'li' => [],
            ];

            echo wp_kses($TipOfDay->getHtmlContent(), $allowedHtml);
            ?>
        </div>
        <div class="a2wl-tip-of-day__footer">
            <?php if ($hasCta) : ?>
                <a class="a2wl-btn a2wl-btn--primary a2wl-tip-of-day__cta"
                   href="<?php echo esc_url($TipOfDay->getCtaUrl()); ?>"
                   target="_blank"
                >
                    <?php echo esc_html($TipOfDay->getCtaLabel() ?: __('Learn More', 'ali2woo')); ?>
                </a>
            <?php endif; ?>
            <button class="a2wl-btn a2wl-btn--secondary a2wl-tip-of-day__dismiss" type="button">
                <?php echo $closeLabel; ?>
            </button>
            <span class="a2wl-tip-of-day__disable-wrap">
                <a href="#" class="a2wl-tip-of-day__disable">
                    <?php echo esc_html_x("Don't show tips anymore", 'modal', 'ali2woo'); ?>
                </a>
            </span>
        </div>
    </div>
</div>

<script>
    const a2wTipOfDayAPI = (function ($, ajaxApi) {
        async function hide(id, nonce) {
            let data = {
                'action': '<?php echo TipOfDayAjaxController::AJAX_METHOD_TIP_OF_DAY; ?>',
                '<?php echo TipOfDayAjaxController::PARAM_ID ?>': id,
                'ali2woo_nonce': nonce,
            };
            try {
                return await ajaxApi.doAjax(data);
            } catch (error) {
                console.log('hide TipOfDay #' + id + ' Error!', error);
                return { state: 'error', message: error.message };
            }
        }

        async function disableAll(nonce) {
            let data = {
                'action': '<?php echo TipOfDayAjaxController::AJAX_METHOD_DISABLE_TIPS; ?>',
                'ali2woo_nonce': nonce,
            };
            try {
                return await ajaxApi.doAjax(data);
            } catch (error) {
                console.log('disable all tips Error!', error);
                return { state: 'error', message: error.message };
            }
        }

        return { hide: hide, disableAll: disableAll };
    })(jQuery, a2wAjaxApi);

    jQuery(function ($) {
        let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';
        let $modal = $('.a2wl-tip-of-day-overlay');
        let tipId = $modal.data('id');

        let closeModal = function () {
            $modal.removeClass('opened');
            a2wTipOfDayAPI.hide(tipId, nonce_action);
        };

        $modal.find('.a2wl-tip-of-day__dismiss').on('click', function (event) {
            event.preventDefault();
            closeModal();
        });

        $modal.find('.a2wl-tip-of-day__close').on('click', function (event) {
            event.preventDefault();
            closeModal();
        });

        $modal.find('.a2wl-tip-of-day__disable').on('click', function (event) {
            event.preventDefault();
            a2wTipOfDayAPI.disableAll(nonce_action).then(function () {
                $modal.removeClass('opened');
            });
        });

        $modal.on('click', function (event) {
            if (event.target === this) {
                closeModal();
            }
        });
    });
</script>
