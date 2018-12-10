To migrate your project to new version: 

#### EntityControlTrait

All relations variables moved form method call to withRelations method.

Methods : withRelations, withTrashed, onlyTrashed return this and using for making chains;

Method update: now is used for updated first entity in database.  

Method updateMany: now is used for updating multiple entities in database. 

Method firstOrCreate: now is accepting 2 parameters.

#### FilesUploadTrait 

This class is now used to upload files, all other clasess is now @depricated.

#### FixturesTrait

jsonExport now have jsonExport($fixture, $data) call.


