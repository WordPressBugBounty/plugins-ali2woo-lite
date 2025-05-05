<?php

/**
 * Description of ImportListService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
class ImportListService
{
    protected AddProductToImportListProcess $AddProductToImportListProcess;

    public function __construct(
        AddProductToImportListProcess $AddProductToImportListProcess,
    ) {
        $this->AddProductToImportListProcess = $AddProductToImportListProcess;
    }

    /**
     * @throws ServiceException
     */
    public function addProductsFromCsvFile(string $fileName): ProductsFromFileResult
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $f = fopen($fileName, 'r');

        if ($f === false) {
            throw new ServiceException(
                _x( "Can not read the file.", 'error text', 'ali2woo')
            );
        }

        $externalProductIds = [];

        while ($row = fgetcsv($f, 1024, ';', '"', "\\")) {
            $id_or_url = urldecode(trim($row[0]));

            if (preg_match('/.*\/([0-9]+)\.html/', $id_or_url, $matches)) { //get id from url
                $id = (int)$matches[1];
            } else { //is not url
                //trim all not number symbols
                $id = (int)preg_replace('/[\D]+/', '', $id_or_url);
            }

            if (!$id) {
                continue;
            }

            $externalProductIds[] = $id;
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($f);

        foreach ($externalProductIds as $externalProductId) {
            $this->AddProductToImportListProcess->pushToQueue(
                $externalProductId,
            );
        }

        $this->AddProductToImportListProcess->dispatch();

        return new ProductsFromFileResult(count($externalProductIds), []);
    }

    public function getCountryFromList(array $importedProduct): array
    {
        $countryFromList = ['CN'];
        if (!empty($product[ImportedProductService::FIELD_COUNTRY_FROM_LIST])) {
            $countryFromList = implode(";", $product[ImportedProductService::FIELD_COUNTRY_FROM_LIST]);
        }

        $result = [];

        foreach ($countryFromList as $countryCode) {
            $result[$countryCode] = Country::get_country($countryCode);
        }

        return $result;
    }
}
