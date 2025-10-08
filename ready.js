const fs = require('fs');
const path = require('path');
const archiver = require('archiver');
const dirName = path.basename(__dirname);
let version = 'x.x.x';
(function () {
  const data = fs.readFileSync(dirName + '.php', 'utf8');
  const pattern = /Version:\s*(?<version>[\d\.]+)/;
  const result = pattern.exec(data);
  version = result.groups.version;
  console.log('Version: ', version);
}());

// create a file to stream archive data to.
const output = fs.createWriteStream(`D:/plugins/${dirName}-v.${version}.zip`);
const archive = archiver('zip');



// listen for all archive data to be written
//'close' event is fired only when a file descriptor is involved
output.on('close', function () {
  console.log(archive.pointer() + ' total bytes');
  console.log(`File created: D:/plugins/${dirName}-v.${version}.zip`);
});


// good practice to catch warnings (ie stat failures and other non-blocking errors)
archive.on('warning', function (err) {
  if (err.code === 'ENOENT') {
    // log warning
  } else {
    // throw error
    throw err;
  }
});
archive.glob(
  //pattern match everything 
  '**',
  {
    //where to look for
    cwd: __dirname,
    ignore: [
      //ignore node_modules dir
      'node_modules/**',
      //Project specific
      'assets/js/node_modules/**',
      'assets/js/src/**',
      'assets/js/*.mjml',
      'assets/js/*.json',
      'assets/js/*.map',
      'assets/js/ready.js',
      //ignore filename.ext 
      '**ready.js',
      //ignore json extension all over
      '*.json']

  },
  {
    //add parent directory name
    prefix: dirName
  });

// good practice to catch this error explicitly
archive.on('error', function (err) {
  throw err;
});

// pipe archive data to the file
archive.pipe(output);


// finalize the archive (ie we are done appending files but streams have to finish yet)
// 'close', 'end' or 'finish' may be fired right after calling this method so register to them beforehand
archive.finalize();
