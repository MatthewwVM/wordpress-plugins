<?php
/**
 * Plugin Name: Multi-Cluster Capacity Beta
 * Plugin URI: https://dinocloud.net/
 * Description: A tool to estimate vCPU, Memory, and Storage for up to three clusters and display totals.
 * Version: 1.1
 * Author: Matt but really ChatGPT
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Register the shortcode [multi_cluster_capacity]
function multi_cluster_capacity_shortcode() {
    ob_start(); ?>

    <div id="multi-cluster-capacity">
        <h3>Multi-Cluster Capacity Calculator</h3>

        <?php for ($i = 1; $i <= 3; $i++) { ?>
            <fieldset>
                <legend><strong>Cluster <?php echo $i; ?></strong></legend>

                <label><b>Node Type:<b></label>
                <select id="node-type-<?php echo $i; ?>">
                    <option value="AV36">AV36 (36 CPUs, 576GB, 15.2TB)</option>
                    <option value="AV36P">AV36P (36 CPUs, 768GB, 19.2TB)</option>
                    <option value="AV52">AV52 (52 CPUs, 1536GB, 38.4TB)</option>
                    <option value="AV64">AV64 (64 CPUs, 1024GB, 15.36TB)</option>
                </select>

                <br>

                <label><b>CPU Ratio:<b></label>
                <select id="cpu-ratio-<?php echo $i; ?>">
                    <option value="2">2:1</option>
                    <option value="3">3:1</option>
                    <option value="4" selected>4:1</option>  <!-- Default selection -->
                    <option value="5">5:1</option>
                    <option value="6">6:1</option>
                </select>

                <br>

                <label><b>Storage Policy:<b></label>
                <select id="storage-policy-<?php echo $i; ?>">
                    <option value="RAID1">FTT=1 Mirroring (RAID 1)</option>
                    <option value="RAID5">FTT=1 Erasure Coding (RAID 5)</option>
                    <option value="RAID6">FTT=2 Erasure Coding (RAID 6)</option>
                </select>

                <br>

                <label><b>Dedupe/Compression Ratio:<b></label>
                <select id="dedupe-ratio-<?php echo $i; ?>">
                    <option value="1.25">1.25</option>
                    <option value="1.5" selected>1.5</option>  <!-- Default selection -->
                    <option value="1.75">1.75</option>
                </select>

                <br>

                <label><b>Number of Nodes (0-16):<b></label>
                <input type="number" id="node-count-<?php echo $i; ?>" min="0" max="16">

                <p><strong>Results:</strong></p>
                <p id="vcpu-result-<?php echo $i; ?>"></p>
                <p id="memory-result-<?php echo $i; ?>"></p>
                <p id="storage-result-<?php echo $i; ?>"></p>
            </fieldset>
        <?php } ?>

        <fieldset>
            <legend><strong>Totals Across All Clusters</strong></legend>
            <p id="total-vcpu"></p>
            <p id="total-memory"></p>
            <p id="total-storage"></p>
        </fieldset>

        <canvas id="clusterChart" width="400" height="200"></canvas>
        
        <button onclick="calculateClusterCapacity()">Calculate</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function calculateClusterCapacity() {
            const nodeSpecs = {
                "AV36": { cores: 36, ram: 576, storage: 15200 },
                "AV36P": { cores: 36, ram: 768, storage: 19200 },
                "AV52": { cores: 52, ram: 1536, storage: 38400 },
                "AV64": { cores: 64, ram: 1024, storage: 15360 }
            };

            let totalVCPU = 0, totalMemory = 0, totalStorage = 0;
            let clusterLabels = [];
            let clusterValues = [];

            for (let i = 1; i <= 3; i++) {
                let nodeType = document.getElementById(`node-type-${i}`).value;
                let cpuRatio = parseInt(document.getElementById(`cpu-ratio-${i}`).value);
                let storagePolicy = document.getElementById(`storage-policy-${i}`).value;
                let dedupeRatio = parseFloat(document.getElementById(`dedupe-ratio-${i}`).value);
                let nodeCount = parseInt(document.getElementById(`node-count-${i}`).value);

                if (!nodeCount || nodeCount < 0) {
                    continue;
                }

                let node = nodeSpecs[nodeType];

                let vCPU = node.cores * cpuRatio * nodeCount;
                let memory = node.ram * nodeCount;
                let rawCapacity = node.storage * nodeCount;

                let usableCapacity = 0;
                if (storagePolicy === "RAID1") {
                    usableCapacity = (((rawCapacity / 1.25) / 2) * dedupeRatio);
                } else if (storagePolicy === "RAID5") {
                    usableCapacity = (((rawCapacity / 1.25) / 1.33) * dedupeRatio);
                } else if (storagePolicy === "RAID6") {
                    usableCapacity = (((rawCapacity / 1.25) / 1.5) * dedupeRatio);
                }

                totalVCPU += vCPU;
                totalMemory += memory;
                totalStorage += usableCapacity;

                document.getElementById(`vcpu-result-${i}`).innerHTML = `vCPU: ${vCPU}`;
                document.getElementById(`memory-result-${i}`).innerHTML = `Memory: ${memory} GB`;
                document.getElementById(`storage-result-${i}`).innerHTML = `Usable Storage: ${Math.round(usableCapacity)} GB`;

                clusterLabels.push(`Cluster ${i}`);
                clusterValues.push(Math.round(usableCapacity));
            }

            document.getElementById("total-vcpu").innerHTML = `Total vCPU: ${totalVCPU}`;
            document.getElementById("total-memory").innerHTML = `Total Memory: ${totalMemory} GB`;
            document.getElementById("total-storage").innerHTML = `Total Usable Storage: ${Math.round(totalStorage)} GB`;

            new Chart(document.getElementById("clusterChart"), {
                type: 'pie',
                data: {
                    labels: clusterLabels,
                    datasets: [{
                        label: "Storage Capacity per Cluster (GB)",
                        data: clusterValues,
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                    }]
                }
            });
        }
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('multi_cluster_capacity', 'multi_cluster_capacity_shortcode');
