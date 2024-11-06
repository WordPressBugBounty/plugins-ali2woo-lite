<div class="modal-overlay modal-split-product">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <?php echo esc_html_x('Split Product', 'split feature', 'ali2woo'); ?>
            </h3>
            <a class="modal-btn-close" href="#"></a>
        </div>
        <div class="modal-body">
            <div class="modal-split-product-loader a2wl-load-container" style="padding:80px 0;">
                <div class="a2wl-load-speeding-wheel"></div>
            </div>
            <div class="modal-split-product-content">
                <div class="split-title">
                    <div class="split-name">
                        <?php
                        echo esc_html_x('Select which option you want to use for splitting the product',
                            'split feature',
                            'ali2woo');
                        ?>
                    </div>
                    <div>
                        <?php
                        echo sprintf(
                            _x(' ...or <a href="%s" class="split-mode">Split manually</a>', 'split feature',  'ali2woo'),
                            esc_url('#')
                        );
                        ?>
                    </div>
                </div>
                <div class="split-content">
                    <b> <?php
                        echo esc_html_x(
                                'Select which option you want to use for splitting the product',
                            'Split by',
                        'ali2woo');
                        ?>
                    </b>:
                    <div class="split-attributes"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default modal-close" type="button">
                <?php echo esc_html_x('Cancel', 'split feature', 'ali2woo'); ?>
            </button>
            <button style="display:none" class="btn btn-success do-split-product attributes" type="button">
                <?php echo esc_html_x('Split to ', 'split feature', 'ali2woo'); ?>
                <span class="btn-split-count">0</span>
                <?php echo esc_html_x('Products', 'split feature', 'ali2woo'); ?>
            </button>
            <button style="display:none" class="btn btn-success do-split-product manual" type="button">
                <?php echo esc_html_x('Split product', 'split feature', 'ali2woo'); ?>
            </button>
        </div>
    </div>
</div>
