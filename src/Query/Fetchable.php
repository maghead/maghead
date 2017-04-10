<?php

namespace Maghead\Query;

interface Fetchable {

    public function fetch();

    public function fetchColumn($column = 0);

}
