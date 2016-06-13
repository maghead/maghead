<?php
namespace LazyRecord;

interface ExporterInterface
{
    public function toJson();
    public function toXml();
    public function toYaml();
    public function toArray();
}

