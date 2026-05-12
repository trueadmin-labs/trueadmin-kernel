<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\I18n\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\TranslatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    protected const SUPPORTED = ['zh_CN', 'en'];

    /**
     * @var array<string, string>
     */
    protected const LANGUAGE_ALIASES = [
        'zh' => 'zh_CN',
        'en' => 'en',
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->translator->setLocale($this->resolveLocale($request->getHeaderLine('Accept-Language')));

        return $handler->handle($request);
    }

    protected function resolveLocale(string $header): string
    {
        foreach (explode(',', $header) as $item) {
            $locale = $this->normalizeLocale(trim(explode(';', $item, 2)[0] ?? ''));
            if ($locale !== null) {
                return $locale;
            }
        }

        return (string) $this->config->get('translation.locale', 'zh_CN');
    }

    protected function normalizeLocale(string $locale): ?string
    {
        if ($locale === '') {
            return null;
        }

        $normalized = str_replace('-', '_', $locale);
        if (in_array($normalized, $this->supportedLocales(), true)) {
            return $normalized;
        }

        $language = strtolower(explode('_', $normalized, 2)[0]);
        $alias = $this->localeAliases()[$language] ?? null;

        return is_string($alias) && in_array($alias, $this->supportedLocales(), true) ? $alias : null;
    }

    /**
     * @return list<string>
     */
    protected function supportedLocales(): array
    {
        $locales = $this->config->get('translation.supported_locales', static::SUPPORTED);

        return is_array($locales)
            ? array_values(array_filter($locales, static fn (mixed $locale): bool => is_string($locale) && $locale !== ''))
            : static::SUPPORTED;
    }

    /**
     * @return array<string, string>
     */
    protected function localeAliases(): array
    {
        $aliases = $this->config->get('translation.locale_aliases', static::LANGUAGE_ALIASES);

        return is_array($aliases)
            ? array_filter($aliases, static fn (mixed $locale, mixed $language): bool => is_string($language) && $language !== '' && is_string($locale) && $locale !== '', ARRAY_FILTER_USE_BOTH)
            : static::LANGUAGE_ALIASES;
    }
}
