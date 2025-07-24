<?php
/**
 * Description of ProcessFactory
 *
 * @author Ali2Woo Team
 *
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
namespace AliNext_Lite;;

use Exception;
use DI\Container;
use Throwable;

class BackgroundProcessFactory
{
    public function __construct(
        protected Container $Container
    ){}

    /**
     * @param string $actionCode
     * @return ImportJobInterface
     * @throws Exception
     */
    public function createProcessByCode(string $actionCode): BaseJobInterface
    {
        if ($actionCode == ApplyPricingRulesProcess::ACTION_CODE) {
            return new ApplyPricingRulesProcess();
        }

        if ($actionCode == ImportProcess::ACTION_CODE) {
            return new ImportProcess();
        }

        if ($actionCode == AddProductToImportListProcess::ACTION_CODE) {
            return $this->Container->get(AddProductToImportListProcess::class);
        }

        if ($actionCode == AffiliateCheckProcess::ACTION_CODE) {
            return $this->Container->get(AffiliateCheckProcess::class);
        }

        

        throw new Exception('Unknown process given: ' . $actionCode);
    }

    public function getAll(): array
    {
        $codes = [
            ApplyPricingRulesProcess::ACTION_CODE,
            ImportProcess::ACTION_CODE,
            AddProductToImportListProcess::ACTION_CODE,
            AffiliateCheckProcess::ACTION_CODE,
            
        ];

        $jobs = [];

        foreach ($codes as $code) {
            try {
                $jobs[] = $this->createProcessByCode($code);
            } catch (Throwable $e) {
                a2wl_info_log("[BackgroundProcessFactory] Failed to resolve '{$code}': " . $e->getMessage());
            }
        }

        return $jobs;
    }
}