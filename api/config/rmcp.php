<?php

return [
    'permissions' => [
        'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
        'documents.view', 'documents.create', 'documents.edit', 'documents.delete',
        'incidents.view', 'incidents.create', 'incidents.edit',
        'cases.view', 'cases.create', 'cases.edit', 'cases.submit', 'cases.approve', 'cases.close', 'cases.escalate',
        'directors.view', 'directors.create', 'directors.edit', 'directors.delete',
        'shareholders.view', 'shareholders.create', 'shareholders.edit', 'shareholders.delete',
        'beneficial_owners.view', 'beneficial_owners.create', 'beneficial_owners.edit', 'beneficial_owners.delete',
        'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
        'communications.view', 'communications.create', 'communications.edit', 'communications.delete',
        'audit_logs.view',
        'roles.view', 'roles.edit'
    ],
];
