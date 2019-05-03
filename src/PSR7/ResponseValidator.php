<?php
/**
 * @author Dmitry Lezhnev <lezhnev.work@gmail.com>
 * Date: 02 May 2019
 */
declare(strict_types=1);


namespace OpenAPIValidation\PSR7;


use OpenAPIValidation\PSR7\Exception\MissedResponseHeader;
use OpenAPIValidation\PSR7\Exception\ResponseBodyMismatch;
use OpenAPIValidation\PSR7\Exception\ResponseHeadersMismatch;
use OpenAPIValidation\PSR7\Exception\UnexpectedResponseContentType;
use OpenAPIValidation\PSR7\Exception\UnexpectedResponseHeader;
use OpenAPIValidation\PSR7\Validators\Body;
use OpenAPIValidation\PSR7\Validators\Headers;
use OpenAPIValidation\PSR7\Validators\ValidationStrategy;
use Psr\Http\Message\ResponseInterface;

class ResponseValidator extends Validator
{
    use ValidationStrategy;

    /**
     * @param ResponseAddress $addr
     * @param ResponseInterface $response
     * @throws \Exception
     */
    public function validate(ResponseAddress $addr, ResponseInterface $response): void
    {
        // 0. Find appropriate schema to validate against
        $spec = $this->findResponseSpec($addr);

        // 1. Validate Headers
        try {
            $headersValidator = new Headers();
            $headersValidator->validate($response, $spec->headers);
        } catch (\Throwable $e) {
            switch ($e->getCode()) {
                case 200:
                    throw UnexpectedResponseHeader::fromResponseAddr($e->getMessage(), $addr);
                    break;
                case 201:
                    throw MissedResponseHeader::fromResponseAddr($e->getMessage(), $addr);
                    break;
                default:
                    throw ResponseHeadersMismatch::fromAddrAndCauseException($addr, $e);
            }
        }

        // 2. Validate Body
        try {
            $bodyValidator = new Body();
            $bodyValidator->validate($response, $spec->content);
        } catch (\Throwable $e) {
            switch ($e->getCode()) {
                case 100:
                    throw UnexpectedResponseContentType::fromResponseAddr($e->getMessage(), $addr);
                default:
                    throw ResponseBodyMismatch::fromAddrAndCauseException($addr, $e);
            }
        }
    }
}