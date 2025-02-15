<?php
/**
 * Plugin Name: Azure VMware Solution Sizer - Development Version
 * Plugin URI: https://dinocloud.net/pet-projects/
 * Description: A tool to estimate Azure VMware Solution node requirements.
 * Version: 2.2
 * Author: Your Name
 * License: GPL2
 *
 * Changelog:
 * - v2.2: Added "Total Nodes Required" section with AVS-Logo.png
 * - v2.2: Added AV52 node type
 * - v2.2: Real-time calculation (removes button requirement)
 * - v2.2: Displays total nodes required (max of CPU, Memory, Storage)
 * - v2.2: Images for CPU, Memory, Storage, and Total Nodes
 * - v2.2: Improved UI for better responsiveness
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode function for the AVS Sizer
function avs_sizer_shortcode_dev() {
    ob_start(); ?>

    <div id="avs-sizer">
        <h3>Azure VMware Solution Sizer</h3>

        <label for="node-type">Select Node Type:</label>
        <select id="node-type" onchange="calculateAVSNodes()">
            <option value="AV64">AV64 (64 CPUs, 1024GB RAM, 15.36TB)</option>
            <option value="AV52">AV52 (52 CPUs, 1536GB RAM, 38.40TB)</option>
            <option value="AV36">AV36 (36 CPUs, 576GB RAM, 15.20TB)</option>
            <option value="AV36P">AV36P (36 CPUs, 768GB RAM, 19.20TB)</option>
        </select>

        <label for="vcpus">vCPUs:</label>
        <input type="number" id="vcpus" min="1" required oninput="calculateAVSNodes()">

        <label for="memory">Memory (GB):</label>
        <input type="number" id="memory" min="1" required oninput="calculateAVSNodes()">

        <label for="storage">Used Storage (TB):</label>
        <input type="number" id="storage" min="0.1" step="0.1" required oninput="calculateAVSNodes()">

        <label for="storage-profile">Storage Profile:</label>
        <select id="storage-profile" onchange="calculateAVSNodes()">
            <option value="RAID1">FTT=1 RAID 1 (Mirroring)</option>
            <option value="RAID5">FTT=1 RAID 5 (Erasure Coding)</option>
            <option value="RAID6">FTT=2 RAID 6 (Erasure Coding)</option>
        </select>

        <p><strong>Results:</strong></p>
        <div class="result-container">
            <div>
                <img src="<?php echo plugin_dir_url(__FILE__); ?>img/CPU_AdobeStock_227339770.png" alt="CPU" class="result-img">
                <p id="cpu-result"></p>
            </div>
            <div>
                <img src="<?php echo plugin_dir_url(__FILE__); ?>img/RAM_AdobeStock_227421571.png" alt="Memory" class="result-img">
                <p id="memory-result"></p>
            </div>
            <div>
                <img src="<?php echo plugin_dir_url(__FILE__); ?>img/Storage_AdobeStock_227383488.png" alt="Storage" class="result-img">
                <p id="storage-result"></p>
            </div>
        </div>

        <p><strong>Total Nodes Required:</strong></p>
        <div class="total-result">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>img/AVS-Logo.png" alt="Total Nodes" class="total-img">
            <p id="total-nodes"></p>
        </div>
    </div>

    <script>
        function calculateAVSNodes() {
            let vcpus = parseInt(document.getElementById("vcpus").value);
            let memory = parseInt(document.getElementById("memory").value);
            let storage = parseFloat(document.getElementById("storage").value);
            let nodeType = document.getElementById("node-type").value;
            let storageProfile = document.getElementById("storage-profile").value;

            if (!vcpus || !memory || !storage) {
                document.getElementById("total-nodes").innerHTML = "Enter all inputs.";
                return;
            }

            // Node configurations
            const nodeSpecs = {
                "AV64": { cores: 64, ram: 1024, storage: 15.36 },
                "AV52": { cores: 52, ram: 1536, storage: 38.40 },
                "AV36": { cores: 36, ram: 576, storage: 15.20 },
                "AV36P": { cores: 36, ram: 768, storage: 19.20 }
            };

            let selectedNode = nodeSpecs[nodeType];

            let nodeCPU = Math.ceil((vcpus / 4) / selectedNode.cores);
            document.getElementById("cpu-result").innerHTML = `Compute Nodes: ${nodeCPU}`;

            let nodeMemory = Math.ceil(memory / selectedNode.ram);
            document.getElementById("memory-result").innerHTML = `Memory Nodes: ${nodeMemory}`;

            let rawCapacity = selectedNode.storage;
            let usableCapacity = (storageProfile === "RAID1") ? (((rawCapacity / 1.25) / 2) * 1.5)
                               : (storageProfile === "RAID5") ? (((rawCapacity / 1.25) / 1.33) * 1.5)
                               : (((rawCapacity / 1.25) / 1.5) * 1.5);

            let nodeStorage = Math.ceil(storage / usableCapacity);
            document.getElementById("storage-result").innerHTML = `Storage Nodes: ${nodeStorage}`;

            let totalNodes = Math.max(nodeCPU, nodeMemory, nodeStorage);
            document.getElementById("total-nodes").innerHTML = `Total Nodes Required: ${totalNodes}`;
        }
    </script>

    <style>
        #avs-sizer { border: 1px solid #ccc; padding: 20px; max-width: 500px; background: #f9f9f9; }
        #avs-sizer input, #avs-sizer select { display: block; width: 100%; margin: 5px 0; padding: 8px; }
        .result-container { display: flex; justify-content: space-between; margin-top: 10px; }
        .result-container div, .total-result { text-align: center; width: 30%; }
        .result-img, .total-img { width: 80px; height: auto; }
    </style>

    <?php
    return ob_get_clean();
}

add_shortcode('avs_sizer_dev', 'avs_sizer_shortcode_dev');
