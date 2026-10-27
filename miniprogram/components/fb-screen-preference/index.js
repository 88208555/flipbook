// Old component references resolve to the member page without preference controls.
Component({lifetimes:{attached(){wx.redirectTo({url:'/pages/micro/index?key=mine'});}}});
