<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;

final readonly class InputSanitizerMiddleware
{
    public const ALIAS = 'fortify_cms_input_sanitizer';

    public function __construct(private InputSanitizerService $inputSanitizerService)
    {
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $inputSanitizerDto = InputSanitizerDtoInstance::instance()->get();

        if (!$inputSanitizerDto->cmsEnabled) {
            return $next($request);
        }

        try {
            $this->inputSanitizerService->check(array_merge($request->query->all(), $request->request->all()), $request);
        } catch (BadRequestHttpException) {
            $view = IlluminateView::exists($inputSanitizerDto->view)
                ? $inputSanitizerDto->view
                : View::BAD_REQUEST->value;

            /** @var \Illuminate\Routing\ResponseFactory $response */
            $response = response();

            return $response->view($view, [], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
