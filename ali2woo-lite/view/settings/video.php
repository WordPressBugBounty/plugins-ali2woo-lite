<?php
use AliNext_Lite\AbstractController;
use AliNext_Lite\Settings;
use function AliNext_Lite\get_setting;
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * @var array $addVideoToDescriptionTypes
 */
?>

<form method="post" enctype='multipart/form-data'>
    <?php wp_nonce_field(AbstractController::PAGE_NONCE_ACTION, AbstractController::NONCE); ?>
    <input type="hidden" name="setting_form" value="1"/>
    <div class="panel panel-primary mt20">
        <div class="panel-heading">
            <h3 class="display-inline">
                <?php echo esc_html_x('Video settings', 'Setting title', 'ali2woo'); ?>
            </h3>
        </div>

        <div class="panel-body">
            <div class="field field_inline">
                <div class="field__label">
                    <label for="a2wl_import_video">
                        <strong><?php  esc_html_e('Import product video', 'ali2woo');?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php  esc_html_e('Product video will be available via shortcode', 'ali2woo');?>"></div>
                </div>
                <div class="field__input-wrap">
                    <input type="checkbox" class="field__input form-control" id="a2wl_import_video" name="a2wl_import_video" value="yes" <?php if (get_setting(Settings::SETTING_IMPORT_VIDEO)): ?>checked<?php endif;?>/>
                </div>
            </div>

            <div class="field field_inline">
                <div class="field__label">
                    <label for="a2wl_show_product_video_tab">
                        <strong><?php  esc_html_e('Show product video tab', 'ali2woo');?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php  esc_html_e('Display product video on a separate tab in the frontend', 'ali2woo');?>"></div>
                </div>
                <div class="field__input-wrap">
                    <input type="checkbox" class="field__input form-control" id="a2wl_show_product_video_tab" name="a2wl_show_product_video_tab" value="yes" <?php if (get_setting(Settings::SETTING_SHOW_PRODUCT_VIDEO_TAB)): ?>checked<?php endif;?>/>
                </div>
            </div>

            <div class="field field_inline">
                <div class="field__label">
                    <label for="a2wl_video_tab_priority">
                        <strong><?php _ex('Video tab priority', 'Setting title', 'ali2woo'); ?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php _ex('You can adjust this value to change order of video tab', 'setting description', 'ali2woo'); ?>"></div>
                </div>
                <div class="field__input-wrap">
                    <input type="text" placeholder="50" class="field__input form-control" id="a2wl_video_tab_priority" maxlength="5" name="a2wl_video_tab_priority" value="<?php echo esc_attr(get_setting(Settings::SETTING_VIDEO_TAB_PRIORITY)); ?>" />
                </div>
            </div>

            <div class="field field_inline">
                <div class="field__label">
                    <label for="a2wl_make_video_full_tab_width">
                        <strong><?php  esc_html_e('Make video full tab width', 'ali2woo');?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php  esc_html_e('By default, product videos are displayed in their original width. Enable this option to make product videos have the same width as the tab.', 'ali2woo');?>"></div>
                </div>
                <div class="field__input-wrap">
                    <input type="checkbox" class="field__input form-control" id="a2wl_make_video_full_tab_width" name="a2wl_make_video_full_tab_width" value="yes" <?php if (get_setting(Settings::SETTING_MAKE_VIDEO_FULL_TAB_WIDTH)): ?>checked<?php endif;?>/>
                </div>
            </div>

            <div class="field field_inline">
                <div class="field__label">
                    <label for="a2wl_add_video_to_description">
                        <strong><?php echo esc_html_x('Add video to description', 'Setting title', 'ali2woo'); ?></strong>
                    </label>
                </div>
                <div class="field__input-wrap">
                    <?php $currentAddVideoToDescription = get_setting(Settings::SETTING_ADD_VIDEO_TO_DESCRIPTION);?>
                    <select name="a2wl_add_video_to_description" id="a2wl_add_video_to_description" class="field__input form-control small-input">
                        <?php foreach ($addVideoToDescriptionTypes as $type): ?>
                            <option value="<?php echo $type; ?>"<?php if ($currentAddVideoToDescription == $type): ?> selected<?php endif;?>>
                                <?php echo ucfirst($type); ?>
                            </option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>

        </div>
    <div class="container-fluid">
        <div class="row pt20 border-top">
            <div class="col-sm-12">
                <input class="btn btn-success" type="submit" value="<?php esc_html_e('Save settings', 'ali2woo'); ?>"/>
            </div>
        </div>
    </div>
</form>
