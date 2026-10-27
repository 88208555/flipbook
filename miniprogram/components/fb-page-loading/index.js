Component({
 properties: {
  visible: {type:Boolean,value:false},
  logo: {type:Object,value:{}},
 },
 data: {imageReady:false},
 observers: {
  'logo.image': function(){this.setData({imageReady:false});},
  visible: function(value){if(value)this.setData({imageReady:false});},
 },
 methods: {
  onStop(){},
  onLogoLoad(){this.setData({imageReady:true});},
  onLogoError(){this.setData({imageReady:false});},
 },
});
