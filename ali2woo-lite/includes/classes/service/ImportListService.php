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
    protected ProductImport $ProductImportModel;
    protected PriceFormulaService $PriceFormulaService;
    protected ProductService $ProductService;

    public function __construct(
        ProductImport $ProductImportModel,
        PriceFormulaService $PriceFormulaService,
        ProductService $ProductService
    ) {
        $this->ProductImportModel = $ProductImportModel;
        $this->PriceFormulaService = $PriceFormulaService;
        $this->ProductService = $ProductService;
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

        $products = a2wl_get_transient('a2wl_search_result');
        $idsCount = 0;
        $processErrorsIds = [];

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

            $idsCount++;

            $product = [];

            if ($products && is_array($products)) {
                foreach ($products as $p) {
                    if ($p['id'] == $id) {
                        $product = $p;
                        break;
                    }
                }
            }

            global $wpdb;

            $post_id = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->prepare(
                        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_a2w_external_id' AND meta_value=%s LIMIT 1",
                        $id
                    )
            );
            if (get_setting('allow_product_duplication') || !$post_id) {
                $res = $this->ProductService->loadProductWithShippingInfo($id);
                if ($res['state'] !== 'error') {
                    $product = array_replace_recursive($product, $res['product']);

                    if ($product) {
                        $product = $this->PriceFormulaService->applyFormula($product);

                        $this->ProductImportModel->add_product($product);
                    } else {
                        $processErrorsIds[] = $id;
                    }
                } else {
                    $processErrorsIds[] = $id;
                }
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($f);

        return new ProductsFromFileResult($idsCount, $processErrorsIds);
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