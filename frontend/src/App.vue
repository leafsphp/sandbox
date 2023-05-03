<template>
  <Repl :store="store" :showCompileOutput="false" :clearConsole="false" :showImportMap="false" :output="output"
    :run="run" />
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { Repl, ReplStore } from './components/sandbox/repl';
import axios from 'axios';

import './components/sandbox/style.css';
import './components/sandbox/theme.css';

const output = ref(
  '<div style="display:flex;flex-direction:column;justify-content:center;align-items:center;height:100%;"><img src="https://user-images.githubusercontent.com/26604242/178155909-362f06e6-9da9-473b-b47f-1219b4e65ae2.png"><div style="margin-top:10px;">🚀 Click the run button to compile your code</div></div>'
);

const store = new ReplStore({
  defaultVueRuntimeURL: `https://unpkg.com/vue@3/dist/vue.esm-browser.js`,
});

store.setFiles(
  {
    'request.json': JSON.stringify({
      "method": "GET",
      "path": "/",
      "headers": {},
      "data": {}
    }),
  },
  'index.php'
);

onMounted(() => {
  document.body.classList.add("-is-tutorial");
});

onBeforeUnmount(() => {
  document.body.classList.remove("-is-tutorial");
});

const run = async (files: Record<string, any>) => {
  output.value = '<div style="display:flex;justify-content:center;align-items:center;height:100%;">🚀 Compiling your code...</div>';

  const form = new FormData();
  const rawFiles: any = {};

  Object.keys(files).forEach((filename) => {
    form.set(filename, files[filename].code);
    rawFiles[filename] = files[filename].code;
  });

  let { data: folder } = await axios.post('https://leaf-sandbox-server.herokuapp.com/compile', form);

  if (!folder) {
    return store.state.errors.push('Internal system error, please try again');
  } else {
    store.state.errors = [];
  }

  output.value = '<div style="display:flex;justify-content:center;align-items:center;height:100%;">🏃‍♂️ Running your code...</div>'

  try {
    let config = JSON.parse(files['request.json'].code || '');
    config = config.path ? config : null;

    let { data: res, headers } = await axios({
      url: `https://leaf-sandbox-server.herokuapp.com${folder.folder}${config?.path ?? '/'}`,
      method: config?.method ?? 'GET',
      headers: config?.headers ?? {},
      data: config?.data ?? {},
      params: config?.method?.toUpperCase() === "GET" ? config.data : {},
    });

    if (headers['content-type'] === 'application/json' && typeof res === 'string') {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      return output.value = `<div style="background:white;color:black;">${res.replaceAll('<', '&lt;').replaceAll('>', '&gt;')}</div>`;
    }

    if (typeof res !== 'string') {
      res = `<html><body style="overflow:scroll;background:white;">${JSON.stringify(res)}</body></html>`;
      // return output.value = JSON.stringify(res);
    }

    output.value = `<iframe srcdoc='${res}'></iframe>`;
  } catch (error: any) {
    console.log(error, 'error')
    output.value = '<div style="display:flex;justify-content:center;align-items:center;height:100%;">❌ Could not compile</div>'
    if (error?.response?.data) {
      store.state.errors.push(error.response.data);
    } else {
      store.state.errors.push(error);
    }
  }
};

window.addEventListener('keydown', (e) => {
  e.preventDefault();

  if ((e.key === 'Enter' || e.key === 's') && (e.shiftKey || e.metaKey || e.ctrlKey)) {
    run(store.state.files);
  }
});
</script>

<style>
#app {
  width: 100vw !important;
  height: 100vh !important;
  max-width: unset !important;
  padding: 0 !important;
  margin: 0 !important;
}

.vue-repl {
  height: 100vh !important;
}
</style>
