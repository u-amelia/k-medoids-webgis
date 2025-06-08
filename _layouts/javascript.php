<script src="<?=templates()?>/static/js/components/dark.js"></script>
<script src="<?=templates()?>/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="<?=templates()?>/compiled/js/app.js"></script>

<script src="<?=templates()?>/extensions/jquery/jquery.min.js"></script>

<script src="<?=templates()?>/extensions/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?=templates()?>/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="<?=templates()?>/static/js/pages/datatables.js"></script>

<script src="<?=templates()?>/extensions/parsleyjs/parsley.min.js"></script>
<script src="<?=templates()?>/static/js/pages/parsley.js"></script>


<script>
new DataTable('#table2');
scrollX: true
</script>

<script>
new DataTable('#tablescollY', {
    paging: false,
    scrollCollapse: true,
    scrollY: '260px'
});
</script>

<!-- Custom Script to Auto-hide Alert -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var alert = document.getElementById('success-alert');
    if (alert) {
        setTimeout(function() {
            alert.classList.remove('show');
            alert.classList.add('fade');
        }, 10000); // 10000 milliseconds = 10 seconds
    }
});
</script>

<script>
$(document).ready(function() {
    $('form').parsley();
});
</script>