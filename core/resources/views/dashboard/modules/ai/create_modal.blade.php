<div id="smartend-module-ai" class="modal fade" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="smartend-module-ai-title">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="smartend-module-ai-form">
            <div class="modal-header">
                <h5 class="modal-title" id="smartend-module-ai-title">{{ __('backend.aiModuleCreate') }}</h5>
            </div>
            <div class="modal-body">
                <div id="smartend-module-ai-error" class="alert alert-danger" role="alert" style="white-space: pre-line" hidden></div>
                <label for="smartend-module-ai-description">{{ __('backend.aiModuleDescribe') }}</label>
                <textarea id="smartend-module-ai-description" class="form-control" rows="7" required minlength="10" maxlength="5000" dir="auto" aria-describedby="smartend-module-ai-example"></textarea>
                <small id="smartend-module-ai-example" class="text-muted">{{ __('backend.aiModuleExample') }}</small>
            </div>
            <div class="modal-footer">
                <span id="smartend-module-ai-loading" role="status" class="text-muted" hidden><i class="fa fa-spinner fa-spin"></i> {{ __('backend.aiModuleCreating') }}</span>
                <button type="button" class="btn dark-white" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                <button type="submit" class="btn primary" id="smartend-module-ai-create">{{ __('backend.aiModuleCreateButton') }}</button>
            </div>
        </form>
    </div>
</div>
