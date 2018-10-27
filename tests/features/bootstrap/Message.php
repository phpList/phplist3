<?php

use ZBateson\MailMimeParser\Message as MessageParser;

/**
 * Class Message
 */
class Message
{
    /**
     * @var MessageParser
     */
    private $parser;

    private function __construct(MessageParser $parser)
    {
        $this->parser = $parser;
    }

    public static function from($content)
    {
        $parser = MessageParser::from($content);

        return new static($parser);
    }

    public function getFromAddress()
    {
        return $this->parser->getHeaderValue('from');
    }

    public function getFromName()
    {
        return $this->parser
            ->getHeader('from')
            ->getPersonName()
        ;
    }

    public function getToAddress()
    {
        $values = $this->parser
            ->getHeader('to')
            ->getAddresses()
        ;
        $addresses = [];
        foreach($values as $part){
            $addresses[] = $part->getEmail();
        }
        return $addresses;
    }

    public function getToName()
    {
        $values = $this->parser
            ->getHeader('to')
            ->getAddresses()
        ;
        $addresses = [];
        foreach($values as $part){
            $addresses[] = $part->getName();
        }
        return $addresses;
    }

    public function getTextMessage()
    {
        return $this->parser->getTextContent();
    }
}