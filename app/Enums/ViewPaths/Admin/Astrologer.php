<?php

namespace App\Enums\ViewPaths\Admin;

enum Astrologer
{
    // block--------------------------------
    const BLOCK_LIST = [
        URI => 'block-list',
        VIEW => 'admin-views.astrologers.block.list'
    ];


    // manage--------------------------------
    const MANAGE_LIST = [
        URI => 'manage-list',
        VIEW => 'admin-views.astrologers.manage.list'
    ];
    const MANAGE_ADD = [
        URI => 'manage-add-new',
        VIEW => 'admin-views.astrologers.manage.add-new'
    ];
    const MANAGE_UPDATE = [
        URI => 'manage-update',
        VIEW => 'admin-views.astrologers.manage.edit'
    ];
    const MANAGE_DELETE = [
        URI => 'manage-delete',
        VIEW => ''
    ];
    const MANAGE_DETAIL_OVERVIEW = [
        URI => 'manage-detail-overview',
        VIEW => 'admin-views.astrologers.manage.detail.overview'
    ];
    const MANAGE_DETAIL_ORDER = [
        URI => 'manage-detail-order',
        VIEW => 'admin-views.astrologers.manage.detail.order'
    ];
    const MANAGE_DETAIL_SERVICE = [
        URI => 'manage-detail-service',
        VIEW => 'admin-views.astrologers.manage.detail.service'
    ];
    const MANAGE_DETAIL_SETTING = [
        URI => 'manage-detail-setting',
        VIEW => 'admin-views.astrologers.manage.detail.setting'
    ];
    const MANAGE_DETAIL_TRANSACTION = [
        URI => 'manage-detail-transaction',
        VIEW => 'admin-views.astrologers.manage.detail.transaction'
    ];
    const MANAGE_DETAIL_REVIEW = [
        URI => 'manage-detail-review',
        VIEW => 'admin-views.astrologers.manage.detail.review'
    ];

    const MANAGE_DETAIL_HISTORY = [
        URI => 'manage-detail-history',
        VIEW => 'admin-views.astrologers.manage.detail.history'
    ];
    const MANAGE_STATUS = [
        URI => 'manage-status',
        VIEW => ''
    ];


    // pending--------------------------------
    const PENDING_LIST = [
        URI => 'pending-list',
        VIEW => 'admin-views.astrologers.pending.list'
    ];

    
    // review--------------------------------
    const REVIEW_LIST = [
        URI => 'review-list',
        VIEW => 'admin-views.astrologers.review.list'
    ];
    
    
    // gift--------------------------------
    const GIFT_LIST = [
        URI => 'gift-list',
        VIEW => 'admin-views.astrologers.gift.list'
    ];

    
    // skill--------------------------------
    const SKILL_LIST = [
        URI => 'skill-list',
        VIEW => 'admin-views.astrologers.skill.list'
    ];
    const SKILL_ADD = [
        URI => 'skills_add',
    ];
    const SKILL_UPDATE = [
        URI => 'skill-update',
        VIEW => 'admin-views.astrologers.skill.edit'
    ];
    const SKILL_STATUS = [
        URI => 'category-status',
        VIEW => ''
    ];

    
    // category--------------------------------
    const CATEGORY_LIST = [
        URI => 'category-list',
        VIEW => 'admin-views.astrologers.category.list'
    ];
    const CATEGORY_ADD = [
        URI => 'category-add-new',
        VIEW => 'admin-views.astrologers.category.add-new'
    ];
    const CATEGORY_UPDATE = [
        URI => 'category-update',
        VIEW => 'admin-views.astrologers.category.edit'
    ];
    const CATEGORY_STATUS = [
        URI => 'category-status',
        VIEW => ''
    ];


    // commision--------------------------------
    const COMISSION_LIST = [
        URI => 'comission-list',
        VIEW => 'admin-views.astrologers.comission.list'
    ];
    
}
