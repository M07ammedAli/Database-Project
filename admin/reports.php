<?php
// search/search.php — lives in the /search sub-folder, so step up one level.
include_once(__DIR__ . "/../Movie.php");

$dateFrom  = isset($_POST['from'])     ? trim($_POST['from'])     : '';
$dateTo    = isset($_POST['to'])       ? trim($_POST['to'])       : '';
$creatorId = isset($_POST['creator'])  ? (int)$_POST['creator']   : 0;

// Only run a search once the user has actually submitted something.
$generateReport = isset($_POST['generate']);
$reportType = isset($_POST['report_type']) ? $_POST['report_type'] : '';
$results  = array();
if ($generateReport)
{
    if($reportType === 'most_popular')
    {
        $results = Movie::listMostPopular($dateFrom, $dateTo);
    }
    elseif($reportType === 'user_content')
    {
        $results = Movie::listByCreator($creatorId);
    }
}

$creators = Movie::listCreators();

include_once(__DIR__ . "/../header.php");
?>

<?php if(!empty($results)): ?>
    <h2>Report Results</h2>
    
    <?php $columns = array_keys($results[0]); ?>
    
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?php echo htmlentities(ucwords(str_replace('_', ' ', $col))); ?></th>
                <?php endforeach; ?>
                <!--<th>Title</th>
                <th>Creator</th>
                <th>Views</th>
                <th>Release Date</th>-->
            </tr>
        </thead>

        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <?php foreach ($columns as $col): ?>
                    <td>
                        <?php echo htmlentities($row[$col] ?? ''); ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
            <!--<?php foreach($results as $row): ?>
                <tr>
                    <td><?php echo htmlentities($row['title']); ?></td>
                    <td><?php echo htmlentities($row['username'] ?? 'Unknown'); ?></td>
                    <td><?php echo (int)htmlentities($row['view_count']); ?></td>
                    <td><?php echo htmlentities($row['release_date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>-->
    </table>
<?php elseif(isset($_POST['generate'])): ?>
    <p>No Results found for this report.</p>
<?php endif; ?>

<form method="POST" action="reports.php">
    <label>Report Type</label>
    <select id="reportType" name="report_type">
        <option value="most_popular">Most Popular Movies</option>
        <option value="user_content">Content created by user</option>
    </select><br>
    
    <div id="creatorFilter">
        <h2 for="creator">Creator</h2>
        <select id="dropdownList" name="creator">
            <option value="0">Any creator</option>
            <?php foreach ($creators as $c): ?>
                <option value="<?php echo (int) $c['user_id']; ?>"
                    <?php echo ($creatorId === (int) $c['user_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlentities($c['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div id="dateFilter" style="display:none">
        <h2>Date Range</h2>
        <div style="flex:1; min-width:160px;">
            <div>
                <label for="from">Date from</label>
                <input type="date" id="from" name="from" value="<?php echo htmlentities($dateFrom); ?>">
            </div>

            <div>
                <label for="to">Date to</label>
                <input type="date" id="to" name="to" value="<?php echo htmlentities($dateTo); ?>">
            </div>
        </div>
    </div>
    
    <button type="submit" name="generate" value="1">Generate Report</button>
</form>

<script>
    const reportType = document.getElementById("reportType");
    
    const creatorFilter = document.getElementById("creatorFilter");
    const dateFilter = document.getElementById("dateFilter");
    
    function updateFilters()
    {
        if(reportType.value === "most_popular")
        {
            dateFilter.style.display = "block";
            creatorFilter.style.display = "none";
        }
        if(reportType.value === "user_content")
        {
            dateFilter.style.display = "none";
            creatorFilter.style.display = "block";
        }
    }
    reportType.addEventListener("change", updateFilters);
    
    updateFilters();
</script>