<?php

/**
 * Description of AbstractClient
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

abstract class AbstractClient {

    //todo: deprecated; use convertResponse instead
    protected function handleRequestResult($request): array
    {
        if (is_wp_error($request)) {
            return ResultBuilder::buildError($request->get_error_message());
        }

        if (intval($request['response']['code']) !== 200) {
            $message = $request['response']['code'];
            if (!empty($request['response']['message'])) {
                $message .= ' - ' . $request['response']['message'];
            }

            return ResultBuilder::buildError(
                $message,
                ['error_code' => $request['response']['code']]
            );
        }

        $result = json_decode($request['body'], true);

        if ($result[ApiResponse::FIELD_STATE] === ApiResponse::STATE_ERROR) {
            return ResultBuilder::buildError($result[ApiResponse::FIELD_MESSAGE]);
        }

        return $result;
    }

    /**
     * Check and convert array response to ApiResponse
     * @param $request
     * @return ApiResponse
     */
    protected function convertResponse($request): ApiResponse
    {
        $ApiResponse = (new ApiResponse())->setStateOk();

        if (is_wp_error($request)) {
            return $ApiResponse->setStateError($request->get_error_message());
        }

        if (intval($request['response']['code']) !== 200) {
            $message = $request['response']['code'];
            if (!empty($request['response']['message'])) {
                $message .= ' - ' . $request['response']['message'];
            }

            return $ApiResponse
                ->setStateError($message)
                ->setData(['error_code' => $request['response']['code']]);
        }

        $result = json_decode($request['body'], true);

        if ($result[ApiResponse::FIELD_STATE] === ApiResponse::STATE_ERROR) {

            return $ApiResponse->setStateError($result[ApiResponse::FIELD_MESSAGE]);
        } else {
            unset($result[ApiResponse::FIELD_STATE]);
            $ApiResponse->setData($result);
        }

        return $ApiResponse;
    }
}
