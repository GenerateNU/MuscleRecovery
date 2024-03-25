<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Muscle Recovery Data">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <title>Muscle Recovery Data</title>
  <script>
    // Add a global error event listener early on in the page load, to help ensure that browsers
    // which don't support specific functionality still end up displaying a meaningful message.
    window.addEventListener('error', function (error) {
      if (ChromeSamples && ChromeSamples.setStatus) {
        console.error(error);
        ChromeSamples.setStatus(error.message + ' (Your browser may not support this feature.)');
        error.preventDefault();
      }
    });
  </script>

  <link rel="stylesheet" href="../styles/main.css">

</head>

<body>

<?php

$host = "localhost";
$username = "root";
$user_pass = "usbw";
$database_in_use = "test";

$mysqli = new mysqli($host, $username, $user_pass, $database_in_use);

if ($mysqli->connect_errno) {
   echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

echo $mysqli->host_info . "\n";


$sql = "SELECT userName, muscleData, dateTime FROM data";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    echo "userName: " . $row["userName"]. " - muscleData: " . $row["muscleData"]. " " . $row["dateTime"]. "<br>";
  }
} else {
  echo "0 results";
}
$mysqli->close();

?>


<h1>Muscle Recovery Data</h1>

<!--<p>This sample illustrates the use of the Web Bluetooth API to retrieve basic-->
<!--  device information from a nearby Bluetooth Low Energy Device. You may want to-->
<!--  check out the <a href="device-info-async-await.html">Device Info (Async-->
<!--    Await)</a> sample.</p>-->

<form>
  <button>Connect Wearable</button>
</form>

<form>
<button id="sendButton">Start Session</button>
</form>

<datalist id="services">
  <option value="Timestamp">timestamp</option>
  <option value="Measurement_array">measurementArray</option>
</datalist>


<script>
  var ChromeSamples = {
    log: function () {
      var line = Array.prototype.slice.call(arguments).map(function (argument) {
        return typeof argument === 'string' ? argument : JSON.stringify(argument);
      }).join(' ');

      document.querySelector('#log').textContent += line + '\n';
    },

    clearLog: function () {
      document.querySelector('#log').textContent = '';
    },

    setStatus: function (status) {
      document.querySelector('#status').textContent = status;
    },

    setContent: function (newContent) {
      var content = document.querySelector('#content');
      while (content.hasChildNodes()) {
        content.removeChild(content.lastChild);
      }
      content.appendChild(newContent);
    }
  };
</script>

<h3>Live Output</h3>
<div id="output" class="output">
  <div id="content"></div>
  <div id="status"></div>
  <pre id="log"></pre>
</div>

<div style="display:flex; justify-content: space-between; width:90%; margin:auto;">
  <!-- Main Graph -->
  <div style="width:70%;">
      <canvas id="myChart"></canvas>
  </div>

  <!-- Side Column with Smaller Graphs -->
  <div style="width:20%;">
      <canvas id="smallGraph1" style="margin-bottom:10px;"></canvas>
      <canvas id="smallGraph2" style="margin-bottom:10px;"></canvas>
      <canvas id="smallGraph3"></canvas>
  </div>
</div>

<script>
  let connected = false;
  let offloadDataChar
  let offloadDateTimeChar
  let streamDataChar
  let sessionStartChar

    var mainChart;
   // Sample data for the main graph
    const mainLabels = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
    var mainData = {
        labels: mainLabels,
        datasets: [{
            label: 'Session',
            backgroundColor: 'rgb(255, 99, 132)',
            borderColor: 'rgb(255, 99, 132)',
            data: []
        }]
    };

    // Configuration for the main graph
    var mainConfig = {
        type: 'line',
        data: mainData,
        options: {
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'EMG Signal Value'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Session Data',
                    position: 'top',
                    font: {
                        size: 32
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    };


    // Create the chart
window.onload = function() {
  var ctx = document.getElementById('myChart').getContext('2d');
  mainChart = new Chart(ctx, mainConfig);
};


  function onButtonClick() {
    connectDevice();
  }

  function connectDevice() {
    let filters = [
        { name: "Generate ECE Muscle Recovery" },
        { services: ["4fafc201-1fb5-459e-8fcc-c5c9c331914b"] }
    ];

    let options = { filters: filters };

    log('Requesting Bluetooth Device...');
    log('with ' + JSON.stringify(options));

    navigator.bluetooth.requestDevice(options)
        .then(device => {
            log('Connecting to GATT Server...');
            return device.gatt.connect();
        })
        .then(server => {
            log('Getting Service...');
            return server.getPrimaryService("4fafc201-1fb5-459e-8fcc-c5c9c331914b");
        })
        .then(primaryService => {
            service = primaryService;
            log('Getting Characteristics...');
            return Promise.all([
                service.getCharacteristic("f392f003-1c58-4017-9e01-bf89c7eb53bd"), // Offload Data
                service.getCharacteristic("a5b17d6a-68e5-4f33-abe0-e393e4cd7305"), // Datetime
                service.getCharacteristic("beb5483e-36e1-4688-b7f5-ea07361b26a8"),  // Data
                service.getCharacteristic("87ffeadd-3d01-45cd-89bd-ec5a6880c009")
            ]);
        })
        .then(characteristics => {
            offloadDataChar = characteristics[0];
            offloadDateTimeChar = characteristics[1];
            streamDataChar = characteristics[2];
            sessionStartChar = characteristics[3];
            log('Characteristics found. Adding event listeners...');
            offloadDataChar.addEventListener('characteristicvaluechanged', handleOffloadData);
            offloadDateTimeChar.addEventListener('characteristicvaluechanged', handleOffloadDateTime);
            streamDataChar.addEventListener('characteristicvaluechanged', handleStreamingData);
            offloadDataChar.readValue();
            offloadDateTimeChar.readValue();//[offloadDataChar, offloadDateTimeChar, streamDataChar]
            connected = true;
          })

    let index = 0;
    let sessionLengthInSeconds = 10;
    let mydata = [];

    function handleStreamingData(event) {
      mydata[index] = event.target.value.getUint8(0); //loads data for a second
      log("index:" + index + ", Data:" + mydata[index]);
      if (index == sessionLengthInSeconds) {
        index = 0;
        log('Data From this 10 seconds Session: ');

        mainChart.data.datasets[0].data = mydata;
        mainChart.update();
      }
    }
    
    
  let offloadedData = new Uint8Array(10);
function handleOffloadData(event) {
  let characteristicValue = event.target.value;
  for (let i = 0; i < offloadedData.length; i++) {
    offloadedData[i] = characteristicValue.getUint8(i);
    log("Offloaded Data:" + offloadedData[i]);
  }
}

let offloadedDateTime = new Uint8Array(4);
function handleOffloadDateTime(event) {
  let characteristicValue = event.target.value;
  for (let i = 0; i < offloadedDateTime.length; i++) {
    offloadedDateTime[i] = characteristicValue.getUint8(i);
    log("Offloaded Datetime:" + offloadedDateTime[i]);
  }
}

    // Continuously read the characteristic every second
    setInterval(() => {
      if (streamDataChar) {
        streamDataChar.readValue()
          .then(value => {
            console.log(value.getUint8(0));
          })
          .catch(error => {
            console.error('Error reading characteristic:', error);
          });
      }
    }, 1000);
  }

</script>


<script>
  document.querySelector('form').addEventListener('submit', function (event) {
    event.stopPropagation();
    event.preventDefault();

    if (isWebBluetoothEnabled()) {
      ChromeSamples.clearLog();
      onButtonClick();
    }
  });
</script>


<script>
  log = ChromeSamples.log;

  function isWebBluetoothEnabled() {
    if (navigator.bluetooth) {
      return true;
    } else {
      ChromeSamples.setStatus('Web Bluetooth API is not available.\n' +
                              'Please make sure the "Experimental Web Platform features" flag is enabled.');
      return false;
    }
  }
</script>

<script>
  // Function to send session value to sessionStartChar characteristic
  function sendSessionValue(val) {
    if (connected && sessionStartChar) {
      const encodedValue = new TextEncoder().encode(val);
      sessionStartChar.writeValue(encodedValue)
      
        .then(() => {
          console.log(`Sent value: ${val}`);
        })
        .catch(error => {
          console.error('Failed to send value:', error);
        });
    } else {
      console.log("Device not connected or characteristic is not available.");
    }
  }

// TODO: THIS SHIT JANKY you need to connect, start session, then reconnect

  // Event listener for button press
  document.getElementById('sendButton').addEventListener('mousedown', function(event) {
    console.log("Button pressed");
    sendSessionValue("yes");
  });

  // Event listener for button release
  document.getElementById('sendButton').addEventListener('mouseup', function(event) {
    console.log("Button released");
    sendSessionValue("no");
  });
</script>


<script>
  /* jshint ignore:start */
  (function (i, s, o, g, r, a, m) {
    i['GoogleAnalyticsObject'] = r;
    i[r] = i[r] || function () {
      (i[r].q = i[r].q || []).push(arguments)
    }, i[r].l = 1 * new Date();
    a = s.createElement(o),
      m = s.getElementsByTagName(o)[0];
    a.async = 1;
    a.src = g;
    m.parentNode.insertBefore(a, m)
  })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');
  ga('create', 'UA-53563471-1', 'auto');
  ga('send', 'pageview');
  /* jshint ignore:end */
</script>
</body>
</html>
