<?php

declare(strict_types=1);

return [

    'heading' => 'Activité',

    'events' => [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'restored' => 'Restauration',
    ],

    // Phrases métier par défaut. Variables disponibles :
    // :causer :subject :subject_label :subject_title :event :changes_count :date
    'sentences' => [
        'created' => ':causer a créé :subject.',
        'updated' => ':causer a modifié :subject.',
        'deleted' => ':causer a supprimé :subject.',
        'restored' => ':causer a restauré :subject.',
        'default' => ':causer a effectué l\'action ":event" sur :subject.',
    ],

    'causer' => [
        'system' => 'Système',
        'unknown' => 'Quelqu\'un',
    ],

    'subject' => [
        'unknown' => 'un élément',
        'fallback' => ':label #:id',
    ],

    'changes' => [
        'heading' => 'Changements',
        'count' => '{0} Aucun changement|{1} :count changement|[2,*] :count changements',
        'from_to' => ':old vers :new',
        'set_to' => 'défini sur :new',
        'cleared' => 'effacé',
    ],

    'values' => [
        'null' => 'Non renseigné',
        'empty' => 'Vide',
        'true' => 'Oui',
        'false' => 'Non',
        'list_empty' => 'Aucun élément',
        'redacted' => 'Masqué',
    ],

    'filters' => [
        'label' => 'Filtrer l\'activité',
        'all' => 'Tous les événements',
    ],

    'actions' => [
        'load_more' => 'Charger plus',
        'loading' => 'Chargement',
    ],

    'empty' => [
        'heading' => 'Aucune activité pour le moment',
        'description' => 'Les actions effectuées sur cet élément apparaîtront ici.',
    ],

    'error' => [
        'heading' => 'Impossible de charger l\'activité',
        'description' => 'La source d\'activité n\'a pas pu être lue.',
    ],

    'meta' => [
        'occurred_at' => 'Survenu le :date',
    ],

    'debug' => [
        'heading' => 'Diagnostic de présentation',
        'model_label' => 'Libellé du modèle',
        'record_title' => 'Titre du record',
        'source' => 'Source',
        'renderer' => 'Moteur de phrases',
        'properties' => 'Propriétés disponibles',
    ],

];
