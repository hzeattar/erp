<!-- Raise Support Ticket Modal -->
<div id="raiseSupportTicketModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('app.raiseSupportTicket')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-weight-bold text-dark mb-2">@lang('app.chooseSupportOption')</h2>
                        <p class="text-muted">@lang('app.selectSupportService')</p>
                    </div>

                    <!-- Support Options -->
                    <div class="row">
                        <!-- Envato Support Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="https://cdn.worldvectorlogo.com/logos/envato.svg" alt="Envato" class="h-8 w-8 object-contain mr-3" style="height: 32px; width: 32px;">
                                        <div>
                                            <h5 class="font-weight-bold text-dark mb-1">@lang('app.envatoRegularSupport')</h5>
                                            <p class="text-muted small mb-0">@lang('app.includedWithPurchase')</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">@lang('app.responseTime24To48')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">@lang('app.emailForumSupport')</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">@lang('app.documentationGuides')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-success mr-2"></i>
                                                <span class="text-muted small">@lang('app.communityForumAccess')</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="https://froiden.freshdesk.com/support/tickets/new" target="_blank"
                                       class="btn btn-secondary btn-sm">
                                        <i class="fa fa-external-link-alt mr-1"></i>
                                        @lang('app.raiseTicket')
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Priority Support Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-primary" style="background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);">
                                <div class="card-body">
                                    <div class="position-relative">
                                        <span class="badge badge-primary position-absolute" style="top: 0; right: 0;">
                                            @lang('app.recommended')
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center mb-3">
                                        <img src="https://envato.froid.works/logo-froiden.png" alt="Froiden" class="" style="height: 32px;">
                                        <div>
                                            <h5 class="font-weight-bold text-dark mb-1">@lang('app.prioritySupport')</h5>
                                            <p class="text-muted small mb-0">@lang('app.premiumEnhancementService')</p>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.responseTime4Hours')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.whatsappSupport')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.zoomConsultations')</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.codeDiscussion')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.dedicatedSupportTeam')</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-check text-primary mr-2"></i>
                                                <span class="text-primary font-weight-medium small">@lang('app.priorityQueueAccess')</span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="https://envato.froid.works/priority-support?purchase_code={{ global_setting()->purchase_code }}&utm_source=worksuite_app&utm_campaign=priority_support" target="_blank"
                                       class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus mr-1"></i>
                                        @lang('app.knowMore')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('app.close')</button>
            </div>
        </div>
    </div>
</div>
