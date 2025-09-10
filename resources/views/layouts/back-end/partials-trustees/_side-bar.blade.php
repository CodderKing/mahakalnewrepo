@php
use App\Enums\ViewPaths\Vendor\Chatting;
use App\Enums\ViewPaths\Vendor\Product;
use App\Enums\ViewPaths\Vendor\Profile;
use App\Enums\ViewPaths\Vendor\Refund;
use App\Enums\ViewPaths\Vendor\Review;
use App\Enums\ViewPaths\Vendor\DeliveryMan;
use App\Enums\ViewPaths\Vendor\EmergencyContact;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Shop;
use App\Enums\ViewPaths\Vendor\Order as OrderEnum;
use App\Utils\Helpers;
if (auth('trust')->check()) {
$relationEmployees = auth('trust')->user()->relation_id;
} elseif (auth('trust_employee')->check()) {
$relationEmployees = auth('trust_employee')->user()->relation_id;
}
@endphp
<div id="sidebarMain" class="d-none">
    <aside style="text-align: {{ Session::get('direction') === 'rtl' ? 'right' : 'left' }};"
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container">
            <div class="navbar-vertical-footer-offset pb-0">
                <div class="navbar-brand-wrapper justify-content-between side-logo">
                    @php($shop = \App\Models\DonateTrust::where('id', ($relationEmployees??0))->first())
                    <a class="navbar-brand" href="{{ route('tour-vendor.dashboard.index') }}" aria-label="Front">
                        @if (isset($shop))
                        <img class="navbar-brand-logo-mini for-seller-logo"
                            src="{{ getValidImage(path: 'storage/app/public/donate/trust/' . ($shop['theme_image'] ?? ''), type: 'backend-logo') }}"
                            alt="{{ translate('logo') }}">
                        @else
                        <img class="navbar-brand-logo-mini for-seller-logo"
                            src="{{ dynamicAsset(path: 'public/assets/back-end/img/900x400/img1.jpg') }}"
                            alt="{{ translate('logo') }}">
                        @endif
                    </a>
                    <button type="button" class="d-none js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                        <i class="tio-clear tio-lg"></i>
                    </button>

                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-3">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template="<div class=&quot;tooltip d-none d-sm-block&quot; role=&quot;tooltip&quot;><div class=&quot;arrow&quot;></div><div class=&quot;tooltip-inner&quot;></div></div>"></i>
                    </button>
                </div>
                <div class="navbar-vertical-content">
                    <div class="sidebar--search-form pb-3 pt-4">
                        <div class="search--form-group">
                            <button type="button" class="btn"><i class="tio-search"></i></button>
                            <input type="text" class="js-form-search form-control form--control" id="search-bar-input" placeholder="{{ translate('search_menu') . '...' }}">
                        </div>
                    </div>
                    <ul class="navbar-nav navbar-nav-lg nav-tabs">
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/dashboard*') ? 'show' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.dashboard.index') }}">
                                <i class="tio-home-vs-1-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('dashboard') }}
                                </span>
                            </a>
                        </li>
                        @if (Helpers::Employee_modules_permission('Employee', 'Add Employee', 'View')
                        || Helpers::Employee_modules_permission('Employee', 'Employee List', 'View'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('Employee_Management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        @if (Helpers::Employee_modules_permission('Employee', 'Add Employee', 'View'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/employee/add-employee') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.employee.add-employee') }}">
                                <i class="tio-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('add_Employee') }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @if (Helpers::Employee_modules_permission('Employee', 'Employee List', 'View'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/employee/employee-list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.employee.employee-list') }}">
                                <i class="tio-user nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('Employee_List') }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @endif
                        @if (Helpers::Employee_modules_permission('Ads Management', 'Add Ads', 'View')
                        || Helpers::Employee_modules_permission('Ads Management', 'Ads List', 'View'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('ads_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        @if (Helpers::Employee_modules_permission('Ads Management', 'Add Ads', 'View'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/ads-management/add') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.ads-management.add') }}">
                                <i class="tio-shopping-cart-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('add') }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @if(Helpers::Employee_modules_permission('Ads Management', 'Ads List', 'View'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/ads-management/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.ads-management.list') }}">
                                <i class="tio-shopping-cart-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('list') }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @endif
                        @if (Helpers::Employee_modules_permission('Donation Management', 'Donation History', 'View'))
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/donation-history/list') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('trustees-vendor.donation-history.list') }}">
                                <i class="tio-shopping-cart-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('donation-history') }}
                                </span>
                            </a>
                        </li>
                        @endif
                        @if (Helpers::Employee_modules_permission('Support Management', 'From Vendor', 'View')
                        || Helpers::Employee_modules_permission('Support Management', 'From Admin', 'View'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('help_&_support') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/support-ticket*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('support_Ticket') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('support_Ticket') }}
                                    @if (\App\Models\VendorSupportTicketConv::where('type', 'trust')->where('created_by', 'vendor')->where('vendor_id', ($relationEmployees??0))->where('status', 'open')->count() > 0)
                                    <span class="btn-status btn-xs-status btn-status-danger position-absolute top-0 menu-status"></span>
                                    @endif
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                style="display: {{ Request::is('trustees-vendor/messages/*') || Request::is('trustees-vendor/message/*') ? 'block' : 'none' }}">
                                @if (Helpers::Employee_modules_permission('Support Management', 'From Vendor', 'View'))
                                <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/messages/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('trustees-vendor.messages.index') }}">
                                        <i class="tio-support nav-icon"></i>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('from_vendor') }}
                                            @if (\App\Models\VendorSupportTicketConv::where('type', 'trust')->where('created_by', 'vendor')->where('vendor_id', ($relationEmployees??0))->where('status', 'open')->count() > 0)
                                            <span
                                                class="btn-status btn-xs-status btn-status-danger position-absolute top-0 menu-status"></span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                                @endif 
                                @if (Helpers::Employee_modules_permission('Support Management', 'From Admin', 'View'))
                                <li
                                    class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/message/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('trustees-vendor.message.index') }}">
                                        <i class="tio-support nav-icon"></i>
                                        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            {{ translate('from_admin') }}
                                            @if (\App\Models\VendorSupportTicketConv::where('type', 'trust')->where('created_by', 'admin')->where('vendor_id', ($relationEmployees??0))->where('status', 'open')->count() > 0)
                                            <span
                                                class="btn-status btn-xs-status btn-status-danger position-absolute top-0 menu-status"></span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif 
                        @if (Helpers::Employee_modules_permission('Withdrawal Management', 'List', 'View'))
                        <li class="nav-item {{ Request::is('trustees-vendor/withdraw') ? 'scroll-here' : '' }}">
                            <small class="nav-subtitle" title="">{{ translate('withdraw_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('trustees-vendor/withdraw') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('trustees-vendor.withdraw.index') }}">
                                <i class="tio-wallet-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">
                                    {{ translate('withdraws') }}
                                </span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </aside>
</div>