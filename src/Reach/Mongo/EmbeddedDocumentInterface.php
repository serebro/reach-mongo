<?php

namespace Reach\Mongo;

use Reach\Mongo\DocumentInterface;
use Serializable;

interface EmbeddedDocumentInterface
{
    public function setParent(DocumentInterface $document);

    public function getParent();
}
