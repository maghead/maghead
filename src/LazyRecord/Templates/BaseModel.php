<?php

{% if namespace %}
namespace {{ namespace }};
{% endif %}

use LazyRecord\BaseModel;

class {{ base_name }} extends BaseModel
{
	const schema_proxy_class = '{{ schema_proxy_class }}';

}


