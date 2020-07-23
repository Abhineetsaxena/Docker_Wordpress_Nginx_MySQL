# Docker_Wordpress_Nginx_MySQL
This setup if for Multi-Container Based Application using Docker - WordPress, PHP, MySQL, Nginx on EC2 Instance.

In this setup 3 Containers will be running for NGINX, PHP and MySQL individually. 
Wordpress need to be installed on this setup of 3 Containers. 

Steps : -
1---> Purchase one Virtual Machine from Cloud (I used EC2 Instance)

2---> Workspace is created inside /home/ec2-user/Docker_LEMP_Stack directory : 	
      - Install Wordpress in try_docker directory and unzip the package.
      - Wordpress URL - https://wordpress.org/latest.tar.gz
      - Copy wp-config-sample.php file to try_docker file directory
      - create .env file for all Envrionment Variables -
      
3---> home/ec2-user/Docker_LEMP_Stack/try_docker   (for wordpress)
      -	/home/ec2-user/Docker_LEMP_Stack/try_nginx      (for nginx)
      #In try_docker- 
      -	Wordpress is installed
      -	Dockerfile for php-wordpress
      - Makefile for Docker file
      -	wp-config 
      -	Final docker-compose.yml file
      #In try_nginx –
      -	Dockerfile for nginx server
      - Makefile for Dockerfile
      -	Conf file for nginx

4---> From try_docker directory run Docker-compose command :
      $ docker-compose up –d
      3 containers will be launched –
      Wordpress will be running on publicIP:801 port as per the configuration inside Docker-compose           file.
     
- Docker Container images from my DockerHub Repositories
  
For PHP - https://hub.docker.com/repository/docker/abhineet05/php-wordpress-test
For Nginx/Wordpress https://hub.docker.com/repository/docker/abhineet05/nginx-wordpress-test


