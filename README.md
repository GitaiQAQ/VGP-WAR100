VGP-WAR100
=========================

0. Intriduction

   This file will show you how to build the GPL linux system.
   
Step 1.	Install fedora linux 10 (choose Software Development) on 32bit CPU.

Step 2.	Setup Build Enviornment($means command)

  1) please login as a normal user such as john,and copy the gpl file to normal user folder, such as the folder /home/john
  
  2) $cd /home/john
  
  3) $tar zxvf MINIROUTER_v101.tar.gz
	
  4) $cd SONYROUTER_GPL
	
  5) #cp -rf rsdk-1.3.6-4181-EB-2.6.30-0.9.30 /opt	(ps : switch to root permission)

  6) #rpm -ivh ./build_gpl/fakeroot-1.9.7-18.fc10.i386.rpm
		  
  7) $source ./setupenv	(ps : switch back to normal user permission)
	
Step 3. Building the image

  1) $make
  
  2) $make
	
  3) $make
  
```
     	===================================================
	 You are going to build the f/w images.
	 Both the release and tftp images will be generated.
	 ===================================================
	 Do you want to rebuild the linux kernel ? (yes/no) : yes
```

  4) there are some options need to be selected , please input "enter" key to execute the default action. 
	 
  5) After make successfully, you will find the image file in ./images/.
 
 
## Similar Projects

* [feryw/DIR600B2](https://github.com/feryw/DIR600B2)

* [D-Link DAP-1522 Rev. B](https://github.com/codeworkx/dlink_dap1522b)

* [DIR-850L A1](https://github.com/coolshou/DIR-850L_A1)

* [D-Link DWR-966 v1.0.3CP](https://github.com/brunompena/dwr-966)

* [WD MyNet N900](https://github.com/patrick-ken/MyNet_N900)

More project can search by keywords "This file will show you how to build the GPL linux system."
