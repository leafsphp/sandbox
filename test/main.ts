import { createApp, h, watchEffect, ref } from 'vue'
import { Repl, ReplStore } from '../src'
import axios from 'axios'
;(window as any).process = { env: {} }

const App = {
  setup() {
    const output = ref('')
    const query = new URLSearchParams(location.search)
    const store = new ReplStore({
      serializedState: location.hash.slice(1),
      showOutput: query.has('so'),
      outputMode: query.get('om') || 'preview',
      defaultVueRuntimeURL: import.meta.env.PROD
        ? undefined
        : `${location.origin}/src/vue-dev-proxy`,
      defaultVueServerRendererURL: import.meta.env.PROD
        ? undefined
        : `${location.origin}/src/vue-server-renderer-dev-proxy`
    })

    watchEffect(() => history.replaceState({}, '', store.serialize()))

    const run = async (files: Record<string, any>) => {
      const form = new FormData();
      const rawFiles: any = {};

      Object.keys(files).forEach((filename) => {
        form.set(filename, files[filename].code);
        rawFiles[filename] = files[filename].code;
      });

      let { data: folder } = await axios.post('http://localhost:3600/compile', form);

      if (!folder) {
        return store.state.errors.push('Internal system error, please try again');
      } else {
        store.state.errors = [];
      }
  
      try {
        let { data } = await axios.get(
          `https://leafphp-sandbox-server.fly.dev/${folder.folder}/`
        );

        console.log(rawFiles, data, 'files');

        if (typeof data !== 'string') {
          data = JSON.stringify(data);
        }

        output.value = data;
      } catch (error: any) {
        if (error.response.data) {
          store.state.errors.push(error.response.data);
        } else {
          store.state.errors.push(error);
        }
      }
    };

    // setTimeout(() => {
    // store.setFiles(
    //   {
    //     'index.html': '<h1>yo</h1>',
    //     'main.js': 'document.body.innerHTML = "<h1>hello</h1>"',
    //     'foo.js': 'document.body.innerHTML = "<h1>hello</h1>"',
    //     'bar.js': 'document.body.innerHTML = "<h1>hello</h1>"',
    //     'baz.js': 'document.body.innerHTML = "<h1>hello</h1>"'
    //   },
    //   'index.html'
    // )
    // }, 1000);

    // store.setVueVersion('3.2.8')

    return () =>
      h(Repl, {
        store,
        // layout: 'vertical',
        output: output.value,
        run,
        ssr: true,
        sfcOptions: {
          script: {
            // inlineTemplate: false
          }
        }
        // showCompileOutput: false,
        // showImportMap: false
      })
  }
}

createApp(App).mount('#app')
