jQuery(document).ready(function ($) {
    console.log("HCX IP Calculator script loaded."); // Debugging Step 1

    // Generate vCenter sections dynamically
    $("#num_vcenters").on("change", function () {
        let count = parseInt($(this).val());
        let container = $("#vcenter-sections");
        container.html(""); // Clear existing sections

        for (let i = 1; i <= count; i++) {
            container.append(`
                <div class="vcenter-section" data-id="${i}">
                    <h3>vCenter #${i}</h3>

                    <label>Can the management network reach the AVS management /22?</label>
                    <input type="radio" name="avs_management_${i}" value="yes" class="avs-management"> Yes
                    <input type="radio" name="avs_management_${i}" value="no" class="avs-management"> No

                    <label>Do you plan to use HCX vMotion?</label>
                    <input type="radio" name="hcx_vmotion_${i}" value="yes" class="hcx-vmotion"> Yes
                    <input type="radio" name="hcx_vmotion_${i}" value="no" class="hcx-vmotion"> No

                    <label>Do you have an ExpressRoute connection larger than 1Gbps?</label>
                    <input type="radio" name="expressroute_${i}" value="yes" class="expressroute"> Yes
                    <input type="radio" name="expressroute_${i}" value="no" class="expressroute"> No

                    <label>Are all ESXi vMotion interfaces consistent across all clusters?</label>
                    <input type="radio" name="vmotion_consistency_${i}" value="yes" class="vmotion-consistency"> Yes
                    <input type="radio" name="vmotion_consistency_${i}" value="no" class="vmotion-consistency"> No

                    <div class="vmotion-networks-container" style="display:none;">
                        <label>How many vMotion networks are there?</label>
                        <input type="number" class="vmotion-networks" min="1">
                    </div>

                    <label>Will you be using network extension?</label>
                    <input type="radio" name="network_extension_${i}" value="yes" class="network-extension"> Yes
                    <input type="radio" name="network_extension_${i}" value="no" class="network-extension"> No

                    <div class="network-extension-details" style="display:none;">
                        <label>How many Virtual Distributed Switches or NSX-t Transport Zones do you have?</label>
                        <input type="number" class="vds-count" min="1">

                        <div class="ne-ha-container" style="display:none;">
                            <label>Will you be using Network Extension HA?</label>
                            <input type="radio" name="ne_ha_${i}" value="yes" class="ne-ha"> Yes
                            <input type="radio" name="ne_ha_${i}" value="no" class="ne-ha"> No
                        </div>

                        <div class="network-containers"></div>
                    </div>
                </div>
            `);
        }
    });

    // Show/Hide VMotion Networks Input
    $(document).on("change", ".vmotion-consistency", function () {
        let parent = $(this).closest(".vcenter-section");
        if ($(this).val() === "no") {
            parent.find(".vmotion-networks-container").show();
        } else {
            parent.find(".vmotion-networks-container").hide();
        }
    });

    // Show/Hide Network Extension Section
    $(document).on("change", ".network-extension", function () {
        let parent = $(this).closest(".vcenter-section");
        if ($(this).val() === "yes") {
            parent.find(".network-extension-details").show();
        } else {
            parent.find(".network-extension-details").hide();
            parent.find(".ne-ha-container").hide();
            parent.find(".network-containers").html("");
        }
    });

    // Show NE HA and Generate Network Containers
    $(document).on("input", ".vds-count", function () {
        let parent = $(this).closest(".vcenter-section");
        let count = parseInt($(this).val());
        let container = parent.find(".network-containers");
        container.html(""); // Clear existing containers

        if (count > 0) {
            parent.find(".ne-ha-container").show(); // Show NE HA question
            for (let i = 1; i <= count; i++) {
                container.append(`
                    <div class="network-container">
                        <label>How many networks do you intend to extend concurrently for network container ${i}?</label>
                        <input type="number" class="networks-concurrent" min="1">
                    </div>
                `);
            }
        } else {
            parent.find(".ne-ha-container").hide();
        }
    });

    // Handle HCX Calculation
    $("#calculate-btn").on("click", function () {
        console.log("Calculate button clicked!"); // Debugging Step 2

        let vCenters = [];

        $(".vcenter-section").each(function () {
            let vCenterData = {
                avs_management: $(this).find(".avs-management:checked").val(),
                hcx_vmotion: $(this).find(".hcx-vmotion:checked").val(),
                expressroute: $(this).find(".expressroute:checked").val(),
                vmotion_consistent: $(this).find(".vmotion-consistency:checked").val(),
                vmotion_networks: $(this).find(".vmotion-networks").val() || 0,
                network_extension: $(this).find(".network-extension:checked").val(),
                ne_ha: $(this).find(".ne-ha:checked").val(),
                network_containers: []
            };

            $(this).find(".network-container").each(function () {
                vCenterData.network_containers.push({
                    networks_concurrent: $(this).find(".networks-concurrent").val() || 0
                });
            });

            vCenters.push(vCenterData);
        });

        console.log("Sending data to AJAX:", vCenters); // Debugging Step 3

        $.post(hcx_ajax.ajax_url, {
            action: "calculate_hcx_config",
            vCenters: vCenters
        }, function (response) {
            console.log("AJAX Response:", response); // Debugging Step
            if (response.success) {
                $("#hcx-output").html(`
                    <div class="output-summary">
                        <h3>HCX Configuration</h3>
                        <p style="color: orange;">HCX Connector Appliance: ${response.data.hcx_connectors}</p>
                        <p style="color: orange;"># of IPs for HCX Connector Appliances: ${response.data.hcx_connector_ips}</p>
                        <p style="color: lightblue;">HCX Service Mesh's: ${response.data.service_meshes}</p>
                        <p style="color: lightgreen;">IX Appliances: ${response.data.ix_appliances}</p>
                        <p style="color: darkblue;">WO Appliances: ${response.data.wo_appliances}</p>
                        <p style="color: purple;">NE Appliances: ${response.data.ne_appliances}</p>
                        <p># of IPs for Management Network Profile: ${response.data.mgmt_ips}</p>
                        <p># of IPs for vMotion Network Profile: ${response.data.vmotion_ips}</p>
                        ${response.data.uplink_ips > 0 ? `<p># of IPs for Uplink Network Profile: ${response.data.uplink_ips}</p>` : ""}
                    </div>
                `);
            } else {
                console.error("Error: ", response);
                alert("Error calculating HCX configuration.");
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
        });            
    });
});
