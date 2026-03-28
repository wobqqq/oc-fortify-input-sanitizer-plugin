<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as IlluminateView;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;

final class InputSanitizerMiddleware
{
    public const ALIAS = 'fortify_cms_input_sanitizer';


    public function __construct(private readonly InputSanitizerService $inputSanitizerService)
    {
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $inputSanitizerDto = InputSanitizerDtoInstance::instance()->get();

        if (!$inputSanitizerDto->cmsEnabled) {
            return $next($request);
        }

        try {
            /** @var array<string, string|int|null|array<mixed,mixed>> $query */
            $query = $request->query->all();
            /** @var array<string, string|int|null|array<mixed,mixed>> $post */
            $post = $request->post();
            $input = array_merge($query, $post);
            /** @var array<string, string|int|null|array<mixed,mixed>> $input */

            $this->inputSanitizerService->check($input, $request);
        } catch (BadRequestHttpException $e) {
            $view = IlluminateView::exists($inputSanitizerDto->view)
                ? $inputSanitizerDto->view
                : View::BAD_REQUEST->value;

            /** @var \Illuminate\Routing\ResponseFactory $response */
            $response = response();

            return $response->view($view, [], 400);
        }

        return $next($request);
    }
}
