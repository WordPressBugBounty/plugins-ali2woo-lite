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

class BackgroundProcessFactory
{

    protected AddProductToImportListProcess $AddProductToImportListProcess;

    public function __construct(AddProductToImportListProcess $AddProductToImportListProcess)
    {
        $this->AddProductToImportListProcess = $AddProductToImportListProcess;
    }

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
            return $this->AddProductToImportListProcess;
        }

        throw new Exception('Unknown process given: ' . $actionCode);
    }
}