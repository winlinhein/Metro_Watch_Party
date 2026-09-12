<!-- Dashboard View -->
<div data-tab-panel="dashboard" class="absolute inset-0 px-10 pb-10 w-full min-h-full overflow-y-auto" style="padding-top: 7.5rem;">
    <?php include __DIR__ . '/dashboard/header.php'; ?>
    <?php include __DIR__ . '/../components/trending_movies.php'; ?>
    <?php include __DIR__ . '/dashboard/stats.php'; ?>
    <?php include __DIR__ . '/dashboard/charts.php'; ?>
</div>
