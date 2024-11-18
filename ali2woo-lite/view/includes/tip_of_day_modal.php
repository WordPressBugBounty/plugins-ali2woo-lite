<?php
use AliNext_Lite\AbstractController;
use AliNext_Lite\TipOfDay;
use AliNext_Lite\TipOfDayAjaxController;

/**
 * @var TipOfDay $TipOfDay
 */
?>
<div class="modal-overlay modal-tip-of-day opened"
     data-id="<?php echo esc_attr($TipOfDay->id); ?>"
>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <?php echo esc_html($TipOfDay->getName()); ?>
            </h3>
            <a class="modal-btn-close" href="#"></a>
        </div>
        <div class="modal-body">
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
            ];

            echo wp_kses($TipOfDay->getHtmlContent(), $allowedHtml);
            ?>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default close-btn" type="button">
                <?php echo esc_html_x('Ok', 'modal','ali2woo'); ?>
            </button>
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
                console.log(`hide TipOfDay # ${id} Error!`, error);

                return {
                    state: 'error',
                    message: error.message
                };
            }
        }

        return {
            hide: hide,
        }
    })(jQuery, a2wAjaxApi);

    jQuery(function ($) {
        let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';
        let modalTipOfDay = $(".modal-tip-of-day");

        let hideModalTipOfDay = function() {
            modalTipOfDay.removeClass('opened');
            let id = modalTipOfDay.data('id');
            a2wTipOfDayAPI.hide(id, nonce_action)
        }

        modalTipOfDay.find('.close-btn').on('click', function (event) {
            event.preventDefault();
            hideModalTipOfDay();
        });

        modalTipOfDay.find('.modal-btn-close').on('click', function (event) {
            event.preventDefault();
            hideModalTipOfDay();
        });

    });
</script>
