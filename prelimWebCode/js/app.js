// function addToCache(request, networkResponse) {
//   return caches.open('web-bluetooth')
//     .then(cache => cache.put(request, networkResponse));
// }
//
// function getCacheResponse(request) {
//   return caches.open('web-bluetooth').then(cache => {
//     return cache.match(request);
//   });
// }
//
// function getNetworkOrCacheResponse(request) {
//   return fetch(request).then(networkResponse => {
//     addToCache(request, networkResponse.clone());
//     return networkResponse;
//   }).catch(_ => {
//     return getCacheResponse(request)
//       .then(cacheResponse => cacheResponse || Response.error());
//   });
// }
//
// self.addEventListener('fetch', event => {
//   if (event.request.url.startsWith(self.location.origin)) {
//     event.respondWith(getNetworkOrCacheResponse(event.request));
//   }
// });

// function onButtonClick() {
//   let filters = [];
//
//   // let filterService = document.querySelector('#service').value;
//   // if (filterService.startsWith('0x')) {
//   //   filterService = parseInt(filterService);
//   // }
//   // if (filterService) {
//   //   filters.push({services: [filterService]});
//   // }
//   filters.push({name: filterName});
//   let filterName = document.querySelector('#name').value;
//   if (filterName) {
//     filters.push({name: filterName});
//   }
//
//   let filterNamePrefix = document.querySelector('#namePrefix').value;
//   if (filterNamePrefix) {
//     filters.push({namePrefix: filterNamePrefix});
//   }
//
//   let options = {};
//   if (document.querySelector('#allDevices').checked) {
//     options.acceptAllDevices = true;
//   } else {
//     options.filters = filters;
//   }
//
//   log('Requesting Bluetooth Device...');
//   log('with ' + JSON.stringify(options));
//   navigator.bluetooth.requestDevice(options)
//     .then(device => {
//       log('> Name:             ' + device.name);
//       log('> Id:               ' + device.id);
//       log('> Connected:        ' + device.gatt.connected);
//     })
//     .catch(error => {
//       log('Argh! ' + error);
//     });
// }
