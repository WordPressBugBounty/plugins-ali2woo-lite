<?php

/**
 * Description of PurchaseCodeInfoService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PurchaseCodeInfoService
{
    protected PurchaseCodeInfoFactory $PurchaseCodeInfoFactory;
    protected PlatformClient $PlatformClient;
    protected PurchaseCodeInfoRepository $PurchaseCodeInfoRepository;


    public function __construct(
        PurchaseCodeInfoFactory $PurchaseCodeInfoFactory,
        PlatformClient $PlatformClient,
        PurchaseCodeInfoRepository $PurchaseCodeInfoRepository
    ) {
        $this->PurchaseCodeInfoFactory = $PurchaseCodeInfoFactory;
        $this->PlatformClient = $PlatformClient;
        $this->PurchaseCodeInfoRepository = $PurchaseCodeInfoRepository;
    }

    /**
     * @throws PlatformException
     */
    public function getFromApi(): PurchaseCodeInfo
    {
        $ApiResponse = $this->PlatformClient->serverPing();

        if ($ApiResponse->isStateOk()) {

            $PurchaseCodeInfo = $this->PurchaseCodeInfoFactory->buildFromData(
                $ApiResponse->getData()
            );

            $this->PurchaseCodeInfoRepository->save($PurchaseCodeInfo);

            return $PurchaseCodeInfo;
        }

        throw new PlatformException($ApiResponse->getMessage());
    }

    public function getFromCache(): PurchaseCodeInfo
    {
        return $this->PurchaseCodeInfoRepository->get();
    }

    public function checkAutoUpdateMaxQuota(?PurchaseCodeInfo $PurchaseCodeInfo = null): bool
    {
        if (!A2WL()->isAnPlugin()) {
            return true;
        }

        if (is_null($PurchaseCodeInfo)) {
            $PurchaseCodeInfo = $this->getFromCache();
        }

        $PackageUsageInPercent = $PurchaseCodeInfo->getPackageUsageInPercent();

        if (is_null($PackageUsageInPercent)) {
            return true;
        }

        $autoUpdateMaxQuota = intval(get_setting(Settings::SETTING_AUTO_UPDATE_MAX_QUOTA));

        return $PackageUsageInPercent <= $autoUpdateMaxQuota;
    }
}
