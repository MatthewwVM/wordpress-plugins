<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('wp_ajax_calculate_hcx_config', 'calculate_hcx_config');
add_action('wp_ajax_nopriv_calculate_hcx_config', 'calculate_hcx_config');

function calculate_hcx_config() {
    $vCenters = $_POST['vCenters']; // Array of vCenter data

    $totalVCenters = count($vCenters); // HCX Connector Appliance count
    $totalServiceMeshes = 0;
    $totalIXAppliances = 0;
    $totalWOAppliances = 0;
    $totalNEAppliances = 0;
    $totalMgmtIPs = 0;
    $totalvMotionIPs = 0;
    $totalUplinkIPs = 0;

    foreach ($vCenters as $vCenter) {
        // Calculate Service Meshes
        $serviceMeshCount = ($vCenter['vmotion_consistent'] === 'yes') ? 1 : intval($vCenter['vmotion_networks']);
        $totalServiceMeshes += $serviceMeshCount;

        // IX Appliances match Service Mesh count
        $totalIXAppliances += $serviceMeshCount;

        // WO Appliance Calculation
        $WOAppliances = ($vCenter['expressroute'] === 'yes') ? 0 : $serviceMeshCount;
        $totalWOAppliances += $WOAppliances;

        // NE Appliances Calculation
        $neTotal = 0;
        if ($vCenter['network_extension'] === 'yes') {
            if (isset($vCenter['network_containers'])) {
                foreach ($vCenter['network_containers'] as $netContainer) {
                    $neTotal += ceil(intval($netContainer['networks_concurrent']) / 8);
                }
            }
            if ($vCenter['ne_ha'] === 'yes') {
                $neTotal *= 2;
            }
        }
        $totalNEAppliances += $neTotal;

        // Uplink Network Profile Calculation
        if ($vCenter['avs_management'] === 'no') {
            $uplinkCount = $serviceMeshCount + $neTotal; // Service Mesh + NE Appliances
        } else {
            $uplinkCount = 0;
        }
        $totalUplinkIPs += $uplinkCount;
    }

    // Calculate IPs
    $totalMgmtIPs = $totalNEAppliances + $totalIXAppliances;
    $totalvMotionIPs = $totalIXAppliances;
    $totalHCXConnectorIPs = $totalVCenters; // 🔹 HCX Connector IPs = # of HCX Connectors

    // Debugging Logs for Testing
    error_log("HCX Connector Appliance Count: " . $totalVCenters);
    error_log("Total HCX Connector IPs: " . $totalHCXConnectorIPs);

    // Return results
    wp_send_json_success(array(
        'hcx_connectors' => $totalVCenters,
        'hcx_connector_ips' => $totalHCXConnectorIPs, // 🔹 New Calculation
        'service_meshes' => $totalServiceMeshes,
        'ix_appliances' => $totalIXAppliances,
        'wo_appliances' => $totalWOAppliances,
        'ne_appliances' => $totalNEAppliances,
        'mgmt_ips' => $totalMgmtIPs,
        'vmotion_ips' => $totalvMotionIPs,
        'uplink_ips' => $totalUplinkIPs
    ));
}
