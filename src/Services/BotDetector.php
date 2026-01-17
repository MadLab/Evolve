<?php

namespace MadLab\Evolve\Services;

use Illuminate\Http\Request;

class BotDetector
{
    protected array $userAgentPatterns;

    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('evolve.bot_detection.enabled', true);
        $this->userAgentPatterns = config('evolve.bot_detection.user_agent_patterns', [
            'bot',
            'crawler',
            'spider',
            'googlebot',
            'bingbot',
            'yandexbot',
            'baiduspider',
            'duckduckbot',
            'slurp',
            'facebookexternalhit',
            'linkedinbot',
            'twitterbot',
            'applebot',
            'semrushbot',
            'ahrefsbot',
            'mj12bot',
            'dotbot',
            'petalbot',
            'bytespider',
            'curl',
            'wget',
            'python-requests',
            'java',
            'headless',
            'phantom',
            'selenium',
            'puppeteer',
            'playwright',
        ]);
    }

    public function isBot(?Request $request = null): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $request = $request ?? request();
        $userAgent = strtolower($request->userAgent() ?? '');

        // Empty user agent is suspicious
        if (empty($userAgent)) {
            return true;
        }

        // Check against known bot patterns
        foreach ($this->userAgentPatterns as $pattern) {
            if (str_contains($userAgent, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    public function addPattern(string $pattern): self
    {
        $this->userAgentPatterns[] = $pattern;

        return $this;
    }

    public function setPatterns(array $patterns): self
    {
        $this->userAgentPatterns = $patterns;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }
}
