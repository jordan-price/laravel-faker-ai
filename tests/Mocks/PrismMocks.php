<?php

namespace EchoLabs\Prism;

class Prism 
{
    public static function text() 
    {
        $mockText = new \stdClass();
        $mockText->using = function() use ($mockText) { return $mockText; };
        $mockText->withSystemPrompt = function() use ($mockText) { return $mockText; };
        $mockText->withPrompt = function() use ($mockText) { return $mockText; };
        $mockText->generate = function() { 
            $response = new \EchoLabs\Prism\ValueObjects\TextGeneration\TextGenerationResponse();
            $response->text = "John Doe";
            $response->finishReason = \EchoLabs\Prism\Enums\FinishReason::Stop;
            $response->usage = new \EchoLabs\Prism\ValueObjects\PromptGeneration\PromptGenerationUsage(10, 20);
            return $response;
        };
        
        return $mockText;
    }
    
    public static function swap($mock) 
    {
        // Mock method for swapping the implementation
    }
}

namespace EchoLabs\Prism\Enums;

class Provider 
{
    const Ollama = 'ollama';
    const OpenAI = 'openai';
    const Anthropic = 'anthropic';
    const Mistral = 'mistral';
}

class FinishReason 
{
    const Stop = 'stop';
    const Length = 'length';
    const ContentFilter = 'content_filter';
    const ToolCalls = 'tool_calls';
    const Error = 'error';
    const Other = 'other';
    const Unknown = 'unknown';
}

namespace EchoLabs\Prism\ValueObjects\TextGeneration;

class TextGenerationResponse 
{
    public $text;
    public $finishReason;
    public $usage;
    public $steps = [];
    public $responseMessages = [];
}

namespace EchoLabs\Prism\ValueObjects\PromptGeneration;

class PromptGenerationUsage 
{
    public $promptTokens;
    public $completionTokens;
    
    public function __construct($promptTokens = 0, $completionTokens = 0) 
    {
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
    }
}
