<?php

function getInjector() {
    $injector = new Auryn\Injector;

    $injector->define(GDS\Store::class, ["kind_schema" => GDS\Schema::class]);

    $injector->alias(GDS\Gateway::class, GDS\Gateway\ProtoBuf::class);

    $injector->delegate(GDS\Schema::class, function() {
        return (new GDS\Schema("Code"))
            ->addString("publicId")
            ->addString("code")
            ->addDateTime("created")
            ->addString("ip");
    });

    return $injector;
}