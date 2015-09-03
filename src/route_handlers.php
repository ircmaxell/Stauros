<?php

function fetch(array $vars) {
    $injector = getInjector();
    $mapper = $injector->make('StaurosWeb\DataMapper');

    $entity = $mapper->find($vars['id']);
    if (!$entity) {
        return false;
    }

    $stauros = $injector->make('Stauros\Stauros');

    $entity->escaped = $stauros->scanHTML($entity->code);

    header("Content-Type: application/json");
    echo json_encode($entity);
}

function create() {
    $injector = getInjector();
    $mapper = $injector->make('StaurosWeb\DataMapper');

    $code = file_get_contents("php://input");

    $entity = StaurosWeb\Entity::create($code);
    $mapper->create($entity);

    $stauros = $injector->make('Stauros\Stauros');
    $entity->escaped = $stauros->scanHTML($entity->code);

    header("Content-Type: application/json");
    echo json_encode($entity);
}
