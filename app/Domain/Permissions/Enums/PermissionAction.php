<?php

declare(strict_types=1);

namespace App\Domain\Permissions\Enums;

//I created All Action which is used in project for Admin and User Permissions, you can add more if you need in future
enum PermissionAction: string
{
    case ACCESS = 'access';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case SUSPEND = 'suspend';
    case DEACTIVATE = 'deactivate';
    case MANAGE = 'manage';
    case EXPORT = 'export';
    case REVIEW = 'review';
    case REVOKE = 'revoke';
    case REFUND = 'refund';
    case CANCEL = 'cancel';
    case ASSIGN = 'assign';
    case MODERATE = 'moderate';
    case GENERATE = 'generate';
    case REPLY = 'reply';
    case ESCALATE = 'escalate';
    case TRANSFER = 'transfer';
    case ADJUST = 'adjust';
}