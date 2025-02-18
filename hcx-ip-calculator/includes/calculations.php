<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('wp_ajax_calculate_hcx_config', 'calculate_hcx_config');
add_action('wp_ajax_nopriv_calculate_hcx_config', 'calculate_hcx_config');

function calculate_hcx_config() {
    $vCenters = $_POST['vCenters']; // Array of vCenter data

    $totalServiceMeshes = 0;
    $totalIXAppliances = 0;
    $totalWOAppliances = 0;
    $totalNEAppliances = 0;
    $totalMgmtIPs = 0;
    $totalvMotionIPs = 0;
    $totalUplinkIPs = 0;
    $totalNetworkExtensionIPs = 0;

    foreach ($vCenters as $vCenter) {
        $serviceMeshCount = ($vCenter['vmotion_consistent'] === 'yes') ? 1 : intval($vCenter['vmotion_networks']);
        $totalServiceMeshes += $serviceMeshCount;

        // IX Appliances match Service Mesh count
        $totalIXAppliances += $serviceMeshCount;

        // WO Appliance Calculation
        $WOAppliances = ($vCenter['expressroute'] === 'yes') ? 1 : $serviceMeshCount;
        $totalWOAppliances += $WOAppliances;

        // NE Appliances Calculation
        if ($vCenter['network_extension'] === 'yes') {
            $neTotal = 0;
            if (isset($vCenter['network_containers'])) {
                foreach ($vCenter['network_containers'] as $netContainer) {
                    $neTotal += ceil(intval($netContainer['networks_concurrent']) / 8);
                }
            }

            if ($vCenter['ne_ha'] === 'yes') {
                $neTotal *= 2;
            }

            $totalNEAppliances += $neTotal;
        }
    }

    // Calculate IPs
    $totalMgmtIPs = $totalNEAppliances + $totalIXAppliances;
    $totalvMotionIPs = $totalIXAppliances;

    if ($vCenters[0]['avs_management'] === 'no') {
        $totalUplinkIPs = $totalIXAppliances;
    }

    // Return results
    wp_send_json_success(array(
        'service_meshes' => $totalServiceMeshes,
        'ix_appliances' => $totalIXAppliances,
        'wo_appliances' => $totalWOAppliances,
        'ne_appliances' => $totalNEAppliances,
        'mgmt_ips' => $totalMgmtIPs,
        'vmotion_ips' => $totalvMotionIPs,
        'uplink_ips' => $totalUplinkIPs,
    ));
}
