<!-- Dashboard View -->
<div x-show="currentTab === 'dashboard'" class="absolute inset-0 p-10 w-full min-h-full">
    <?php include __DIR__ . '/dashboard/header.php'; ?>
    <?php include __DIR__ . '/dashboard/stats.php'; ?>
    <?php include __DIR__ . '/dashboard/charts.php'; ?>
</div>
