<?php

use AliNext_Lite\Aliexpress;
use AliNext_Lite\AliexpressHelper;
use AliNext_Lite\ApplyPricingRulesProcess;
use AliNext_Lite\Attachment;
use AliNext_Lite\BackgroundProcessFactory;
use AliNext_Lite\BackgroundProcessService;
use AliNext_Lite\ExternalOrderFactory;
use AliNext_Lite\FulfillmentClient;
use AliNext_Lite\GlobalSystemMessageService;
use AliNext_Lite\Helper;
use AliNext_Lite\ImportAjaxController;
use AliNext_Lite\ImportedProductService;
use AliNext_Lite\ImportedProductServiceFactory;
use AliNext_Lite\ImportListService;
use AliNext_Lite\ImportProcess;
use AliNext_Lite\OrderFulfillmentService;
use AliNext_Lite\Override;
use AliNext_Lite\PermanentAlertService;
use AliNext_Lite\PriceFormulaFactory;
use AliNext_Lite\PriceFormulaRepository;
use AliNext_Lite\PriceFormulaService;
use AliNext_Lite\PriceFormulaSetAjaxController;
use AliNext_Lite\PriceFormulaSetFactory;
use AliNext_Lite\PriceFormulaSetRepository;
use AliNext_Lite\PriceFormulaSetService;
use AliNext_Lite\PriceFormulaSettingsRepository;
use AliNext_Lite\ProductChange;
use AliNext_Lite\ProductImport;
use AliNext_Lite\ProductInfoWidgetController;
use AliNext_Lite\ProductVideoController;

use AliNext_Lite\PromoService;

use AliNext_Lite\Review;
use AliNext_Lite\SplitProductService;
use AliNext_Lite\Synchronize;
use AliNext_Lite\SynchronizePluginDataController;
use AliNext_Lite\TipOfDayAjaxController;
use AliNext_Lite\TipOfDayFactory;
use AliNext_Lite\TipOfDayRepository;
use AliNext_Lite\TipOfDayService;
use AliNext_Lite\VideoShortcodeService;
use AliNext_Lite\Woocommerce;
use function DI\create;
use function DI\get;
use AliNext_Lite\Settings;

return [
    /* helpers */
    'AliNext_Lite\AliexpressHelper' => create(AliexpressHelper::class),

    /* apis */
    'AliNext_Lite\FulfillmentClient' => create(FulfillmentClient::class),

    /* factories */
    'AliNext_Lite\ImportedProductServiceFactory' => create(ImportedProductServiceFactory::class),
    'AliNext_Lite\BackgroundProcessFactory' => create(BackgroundProcessFactory::class),
    'AliNext_Lite\ExternalOrderFactory' => create(ExternalOrderFactory::class),
    'AliNext_Lite\PriceFormulaFactory' => create(PriceFormulaFactory::class),
    'AliNext_Lite\PriceFormulaSetFactory' => create(PriceFormulaSetFactory::class)
        ->constructor(
            get(PriceFormulaFactory::class),
        ),
    'AliNext_Lite\TipOfDayFactory' => create(TipOfDayFactory::class),

    /* repository */
    'AliNext_Lite\PriceFormulaRepository' => create(PriceFormulaRepository::class)
        ->constructor(
            get(PriceFormulaFactory::class)
        ),
    'AliNext_Lite\PriceFormulaSetRepository' => create(PriceFormulaSetRepository::class)
        ->constructor(
            get(PriceFormulaSetFactory::class)
        ),
    'AliNext_Lite\TipOfDayRepository' => create(TipOfDayRepository::class)
        ->constructor(
            get(TipOfDayFactory::class)
        ),

    /* models */
    'AliNext_Lite\Attachment' => create(Attachment::class),
    'AliNext_Lite\Helper' => create(Helper::class),
    'AliNext_Lite\ProductChange' => create(ProductChange::class),
    'AliNext_Lite\ProductImport' => create(ProductImport::class),
    'AliNext_Lite\Woocommerce' => create(Woocommerce::class)
        ->constructor(
            get(Attachment::class),
            get(Helper::class),
            get(ProductChange::class),
            get(VideoShortcodeService::class),
        ),
    'AliNext_Lite\Review' => create(Review::class),
    'AliNext_Lite\Override' => create(Override::class),
    'AliNext_Lite\Aliexpress' => create(Aliexpress::class),

    /* services */
    'AliNext_Lite\ImportedProductService' => create(ImportedProductService::class),
    'AliNext_Lite\BackgroundProcessService' => create(BackgroundProcessService::class)
        ->constructor(get(ApplyPricingRulesProcess::class), get(ImportProcess::class)),
    'AliNext_Lite\PermanentAlertService' => create(PermanentAlertService::class)
        ->constructor(get(BackgroundProcessService::class)),
    'AliNext_Lite\ImportListService' => create(ImportListService::class)
        ->constructor(get(ProductImport::class), get(Aliexpress::class)),
    'AliNext_Lite\OrderFulfillmentService' => create(OrderFulfillmentService::class)
        ->constructor(get(Aliexpress::class), get(ExternalOrderFactory::class)),
    'AliNext_Lite\PriceFormulaService' => create(PriceFormulaService::class)
        ->constructor(
            get(PriceFormulaRepository::class),
            get(PriceFormulaSettingsRepository::class)
        ),
    'AliNext_Lite\PriceFormulaSetService' => create(PriceFormulaSetService::class)
        ->constructor(
            get(PriceFormulaRepository::class),
            get(PriceFormulaSettingsRepository::class),
            get(PriceFormulaSetFactory::class),
            get(BackgroundProcessFactory::class)
        ),
    'AliNext_Lite\SplitProductService' => create(SplitProductService::class)
        ->constructor(
            get(ProductImport::class),
        ),
    'AliNext_Lite\VideoShortcodeService' => create(VideoShortcodeService::class),
    'AliNext_Lite\GlobalSystemMessageService' => create(GlobalSystemMessageService::class),
    'AliNext_Lite\TipOfDayService' => create(TipOfDayService::class)
        ->constructor(
            get(TipOfDayFactory::class),
            get(TipOfDayRepository::class),
            Settings::instance()
        ),
    
    'AliNext_Lite\PromoService' => create(PromoService::class),
    
    /* controllers */
    'AliNext_Lite\ImportAjaxController' => create(ImportAjaxController::class)
        ->constructor(
            get(ProductImport::class), get(Woocommerce::class), get(Review::class),
            get(Override::class), get(Aliexpress::class), get(SplitProductService::class)
        ),

    'AliNext_Lite\PriceFormulaSetAjaxController' => create(PriceFormulaSetAjaxController::class)
        ->constructor(
            get(PriceFormulaSetRepository::class),
            get(PriceFormulaSetService::class)
        ),
    'AliNext_Lite\TipOfDayAjaxController' => create(TipOfDayAjaxController::class)
        ->constructor(
            get(TipOfDayService::class),
            get(TipOfDayRepository::class),
        ),
    'AliNext_Lite\SynchronizePluginDataController' => create(SynchronizePluginDataController::class)
        ->constructor(
            get(TipOfDayRepository::class),
            get(Synchronize::class),
            get(GlobalSystemMessageService::class),
        ),
    'AliNext_Lite\ProductInfoWidgetController' => create(ProductInfoWidgetController::class)
        ->constructor(
            get(VideoShortcodeService::class),
            get(ImportedProductServiceFactory::class)
        ),
    'AliNext_Lite\ProductVideoController' => create(ProductVideoController::class)
        ->constructor(
            get(ImportedProductServiceFactory::class),
        ),
];
