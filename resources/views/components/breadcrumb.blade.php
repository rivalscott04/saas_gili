@once
    <style>
        [data-layout="horizontal"] .app-page-title-box {
            margin-top: 0 !important;
        }
    </style>
@endonce

<!-- start page title -->
<div class="page-title-box d-sm-flex align-items-center justify-content-between app-page-title-box">
    <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>

    <div class="page-title-right">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">{{ $li_1 }}</a></li>
            @if (isset($title))
                <li class="breadcrumb-item active">{{ $title }}</li>
            @endif
        </ol>
    </div>
</div>
<!-- end page title -->
