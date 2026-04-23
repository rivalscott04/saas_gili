<?php $__env->startSection('title'); ?>
    Booking Revenue Recap
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Tour Operations
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Booking Revenue Recap
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-information-line fs-5"></i>
                            <div>
                                <div class="fw-semibold">Cara baca rekap</div>
                                <div class="small">
                                    Gunakan <b>Specific Date</b> untuk 1 hari tertentu. Jika diisi, filter ini akan mengabaikan Date From/To.
                                    Nilai <b>Net Received (IDR)</b> adalah estimasi pendapatan bersih tenant setelah komisi.
                                </div>
                            </div>
                        </div>
                    </div>
                    <form method="GET" class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label">Specific Date</label>
                            <input type="date" class="form-control" name="specific_date" value="<?php echo e($filters['specific_date']); ?>">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo e($filters['date_from']); ?>">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo e($filters['date_to']); ?>">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Channel</label>
                            <select class="form-select" name="channel">
                                <option value="">All Channels</option>
                                <?php $__currentLoopData = $channels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($channel); ?>" <?php echo e($filters['channel'] === $channel ? 'selected' : ''); ?>>
                                        <?php echo e(strtoupper($channel)); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <a href="<?php echo e(route('bookings.recap')); ?>" class="btn btn-soft-secondary">Reset</a>
                            <button type="button" class="btn btn-soft-success" data-bs-toggle="modal" data-bs-target="#recapExportModal">
                                <i class="ri-file-download-line align-bottom me-1"></i>Export File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Daily Net Trend (IDR)</h5>
                </div>
                <div class="card-body">
                    <div id="bookingRevenueTrendChart" class="apex-charts" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Bookings</p>
                    <h4 class="mb-0"><?php echo e(number_format($summary['total_bookings'])); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">Total PAX</p>
                    <h4 class="mb-0"><?php echo e(number_format($summary['total_pax'])); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">Commission (IDR)</p>
                    <h4 class="mb-0">IDR <?php echo e(number_format($summary['commission_idr'], 0)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">Net Received (IDR)</p>
                    <h4 class="mb-0">IDR <?php echo e(number_format($summary['net_idr'], 0)); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Per Channel Recap</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Channel</th>
                                    <th>Bookings</th>
                                    <th>PAX</th>
                                    <th>Net IDR</th>
                                    <th>Avg Net / Booking (IDR)</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $perChannel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $avgNet = ((int) $row->total_bookings) > 0 ? ((float) $row->net_idr / (int) $row->total_bookings) : 0;
                                        $share = (float) ($summary['net_idr'] ?? 0) > 0
                                            ? (((float) $row->net_idr / (float) $summary['net_idr']) * 100)
                                            : 0;
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo e(strtoupper((string) $row->channel_label)); ?></td>
                                        <td><?php echo e(number_format((int) $row->total_bookings)); ?></td>
                                        <td><?php echo e(number_format((int) $row->total_pax)); ?></td>
                                        <td>IDR <?php echo e(number_format((float) $row->net_idr, 0)); ?></td>
                                        <td>IDR <?php echo e(number_format($avgNet, 0)); ?></td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                <?php echo e(number_format($share, 1)); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No data for selected filter.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recapExportModal" tabindex="-1" aria-labelledby="recapExportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="<?php echo e(route('bookings.recap.export')); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recapExportModalLabel">Export Booking Recap</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="specific_date" value="<?php echo e($filters['specific_date']); ?>">
                        <input type="hidden" name="date_from" value="<?php echo e($filters['date_from']); ?>">
                        <input type="hidden" name="date_to" value="<?php echo e($filters['date_to']); ?>">
                        <input type="hidden" name="channel" value="<?php echo e($filters['channel']); ?>">

                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select" name="format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel (.xls)</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Delimiter</label>
                            <select class="form-select" name="delimiter">
                                <option value="semicolon">Titik Koma (;)</option>
                                <option value="colon">Titik Dua (:)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-file-download-line align-bottom me-1"></i>Download
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var trendData = <?php echo json_encode(($trendDaily ?? collect())->map(fn($row) => [
                'date' => (string) ($row->trend_date ?? ''), 'net_idr' => (float) ($row->net_idr ?? 0), ])->values()->all()) ?>;
            var target = document.querySelector('#bookingRevenueTrendChart');
            if (!target) {
                return;
            }

            var labels = trendData.map(function (item) { return item.date; });
            var values = trendData.map(function (item) { return Math.round(item.net_idr); });

            var options = {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 3 },
                series: [{ name: 'Net IDR', data: values }],
                xaxis: { categories: labels },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return 'IDR ' + Number(value || 0).toLocaleString('id-ID');
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (value) {
                            return 'IDR ' + Number(value || 0).toLocaleString('id-ID');
                        }
                    }
                },
                noData: { text: 'No trend data for selected filter.' },
                colors: ['#0ab39c']
            };

            new ApexCharts(target, options).render();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-bookings-recap.blade.php ENDPATH**/ ?>