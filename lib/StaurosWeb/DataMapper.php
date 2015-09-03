<?php

namespace StaurosWeb;

use GDS\Store;

class DataMapper {

    protected $store;

    public function __construct(Store $store) {
        $this->store = $store;
    }

    public function find($id) {
        $item = $this->store->fetchOne("SELECT * FROM Code WHERE publicId = @publicId", ["publicId" => $id]);
        if (!$item) {
            return false;
        }
        return new Entity($item->code, $item->publicId, $item->created, $item->ip);
    }

    public function create(Entity $entity) {
        $this->store->upsert($this->store->createEntity([
            "publicId" => $entity->publicId,
            "code" => $entity->code,
            "created" => $entity->created,
            "ip" => $entity->ip,
        ]));
    }

}