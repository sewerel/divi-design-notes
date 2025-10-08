const fs = require('fs');
const exPath = '/plugins/divi-design-notes'
const copyPath = '../../'
const structure = [
  {dir:'',files:[
    'divi-design-notes.php',
    'license.txt'

  ]},
  {dir:'/assets/js',files:[
    'main.js'
  ]},
  {dir:'/assets/css',files:[
    'style.css',
    'style.min.css'
  ]},
  {dir:'/assets/mail',files:[
    'mail.php'
  ]},
]
structure.forEach(item => {
  fs.mkdir(exPath+item.dir,{ recursive: true }, callback);
})
structure.forEach(item => {
  item.files.forEach(file => {
    fs.copyFile(copyPath+item.dir+'/'+file, exPath+item.dir+'/'+file, callback)
  })

})

function callback(err) {
  if (err) throw err;
  console.log('Dir of file created');
}

