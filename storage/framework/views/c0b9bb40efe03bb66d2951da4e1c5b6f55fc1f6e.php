    

    <?php $__env->startSection('content'); ?>
                <section class="pos-updates">
                    <h1>Inventory Reports</h1>
                    <div class="pos-updates__serach-box">
                        <form action="" method="get" name="search-tool">
                            <div class="search-criteria-box">
                                <label for="search-ini-date">Fecha inicio</label>
                                <input type="date" name="search-ini-date" id="search-ini-date" value="<?php echo e($searchIniDate); ?>" placeholder="YYYY-MM-DD"/>
                            </div>
                            <div class="search-criteria-box">
                                <label for="search-end-date">Fecha fin</label>
                                <input type="date" name="search-end-date" id="search-end-date" value="<?php echo e($searchEndDate); ?>" placeholder="YYYY-MM-DD" />
                            </div>
                            <div class="search-criteria-box">
                                <button type="">Buscar</button>
                            </div>
                        </form>
                    </div>
                    <div class="pos-updates__list">
                        <table class="pos-updates__list__maintable">
                            <thead>
                                <tr>
                                    <th>
                                    Fecha
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($reports->isEmpty()): ?>
                                <tr class="update__tr 1">
                                    <td>
                                        There aren't inventory reports for this criteria
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php echo $report->inventoryReportListView->render(); ?>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                </section>    
                    
    <?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.sleefs-layout', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>