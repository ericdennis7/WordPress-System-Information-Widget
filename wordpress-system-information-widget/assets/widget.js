jQuery(document).ready(function($) {
    $('.sysinfo-toggle').on('change', function() {
        const prefs = {};
        $('.sysinfo-toggle').each(function() {
            const section = $(this).data('section');
            const visible = $(this).is(':checked');
            prefs[section] = visible;
        });

        $.post(sysInfoDashboard.ajax_url, {
            action: 'save_sysinfo_prefs',
            prefs: prefs,
            _ajax_nonce: sysInfoDashboard.nonce
        }, function(response) {
            if (response.success) location.reload();
            else alert('Error saving preference');
        });
    });
});
