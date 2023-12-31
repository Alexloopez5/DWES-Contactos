<?php

namespace App\Controller;

use App\Entity\Contacto;
use App\Entity\Provincia;
use App\Form\ContactoType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ContactoController extends AbstractController
{
    private $contactos = [
        1 => ["nombre" => "Juan Pérez", "telefono" => "524142432", "email" => "juanp@ieselcaminas.org"],
        2 => ["nombre" => "Ana López", "telefono" => "58958448", "email" => "anita@ieselcaminas.org"],
        5 => ["nombre" => "Mario Montero", "telefono" => "5326824", "email" => "mario.mont@ieselcaminas.org"],
        7 => ["nombre" => "Laura Martínez", "telefono" => "42898966", "email" => "lm2000@ieselcaminas.org"],
        9 => ["nombre" => "Nora Jover", "telefono" => "54565859", "email" => "norajover@ieselcaminas.org"]
    ];   

    #[Route('/contacto', name: 'app_contacto')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contactos = $repositorio->findAll();    
        return $this->render("lista_contactos.html.twig", [
            'contactos' => $contactos
        ]);
        
    }

    #[Route('/contacto/insertar', name: 'insertar_contacto')]
    public function insertar(ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        foreach($this->contactos as $c){
            $contacto = new Contacto();
            $contacto->setNombre($c["nombre"]);
            $contacto->setTelefono($c["telefono"]);
            $contacto->setEmail($c["email"]);
            $entityManager->persist($contacto);
        }
        try
        {
            $entityManager->flush();
            return new Response("Contactos insertados");
        } catch (\Exception $e){
            return new Response("Error insertando objetos " . $e->getMessage());
        }
    }

    #[Route('/contacto/nuevo', name: 'nuevo_contacto')]
    public function nuevo(ManagerRegistry $doctrine, Request $request) {
        $contacto = new Contacto();

        $formulario = $this->createForm(ContactoType::class, $contacto);
        $formulario->handleRequest($request);

        if($formulario->isSubmitted() && $formulario->isValid()){
            $contacto = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($contacto);
            $entityManager->flush();
            return $this->render("ficha_contacto.html.twig", ["contacto"=>$contacto ,"codigo" => $contacto->getId()]);
        }
    
        return $this->render('nuevo.html.twig', array(
            'formulario' => $formulario->createView()
        ));
    }

    #[Route('/contacto/editar/{codigo}', name: 'editar_contacto')]
    public function editar(ManagerRegistry $doctrine, Request $request,$codigo, SluggerInterface $slugger) {
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);
        $user = $this->getUser();
        if($user){
            if($contacto){
                $formulario = $this->createForm(ContactoType::class, $contacto);
                $formulario->handleRequest($request);
    
                if($formulario->isSubmitted() && $formulario->isValid()){
                    $contacto = $formulario->getData();
                    $file = $formulario->get('file')->getData();
                    if ($file) {
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        // this is needed to safely include the file name as part of the URL
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                        // Move the file to the directory where images are stored
                        try {

                            $file->move(
                                $this->getParameter('images_directory'), $newFilename
                            );
                            $filesystem = new Filesystem();
                            $filesystem->copy(
                                $this->getParameter('images_directory') . '/'. $newFilename, 
                                $this->getParameter('portfolio_directory') . '/'.  $newFilename, true);

                        } catch (FileException $e) {
                            // ... handle exception if something happens during file upload
                        }

                        // updates the 'file$filename' property to store the PDF file name
                        // instead of its contents
                        $contacto->setFile($newFilename);
                    }
                    $entityManager = $doctrine->getManager();
                    $entityManager->persist($contacto);
                    $entityManager->flush();
                }
                return $this->render('editar.html.twig', array(
                    'formulario' => $formulario->createView()
                ));
            }else{
                return $this->redirectToRoute("nuevo_contacto");
            }
        }else{
            return $this->redirectToRoute("app_login");
        }
    }
    
    #[Route('/contacto/update/{id}/{nombre}', name: 'modificar_contacto')]
    public function update(ManagerRegistry $doctrine, $id, $nombre): Response{
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($id);
        if($contacto){
            $contacto->setnombre($nombre);
            try
            {
                $entityManager->flush();
                return $this->render("ficha_contacto.html.twig", ["contacto" => $contacto]);
            }catch (\Exception $e){
                return new Response("Error insertando objetos");
            }
        }else{
            return $this->render("ficha_contacto.html.twig", ["contacto" => null]);
        }
    }

    #[Route('/contacto/delete/{id}', name: 'eliminar_contacto')]
    public function delete(ManagerRegistry $doctrine, $id): Response{
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($id);

        if($contacto){
            //Si existe el contacto
            try
            {
                //Lo borro
                $entityManager->remove($contacto);
                $entityManager->flush();
                return $this->redirectToRoute("app_contacto");
            }catch(\Exception $e){
                return new Response("Error eliminando objeto ". $e->getMessage());
            }
        }else{
            //Si no me redirecciona a los contactos que hay
            return $this->render("ficha_contacto.html.twig", ["contacto" => null]);
        }
    }

    #[Route('/contacto/insertarConProvincia', name: 'insertar_con_provincia_contacto')]
    public function insertarConProvincia(ManagerRegistry $doctrine): Response{
        $entityManager = $doctrine -> getManager();

        $provincia = new Provincia();
        $provincia->setNombre("Alicante");
        
        $contacto = new Contacto();
        $contacto->setNombre("Inserción de prueba con provincia");
        $contacto->setTelefono("900220022");
        $contacto->setEmail("insercion.de.prueba.provincia@contacto.es");
        $contacto->setProvincia($provincia);

        $entityManager->persist($provincia);
        $entityManager->persist($contacto);

        $entityManager->flush();
        return $this->render("ficha_contacto.html.twig", [
            "contacto"=>$contacto
        ]);
    }

    #[Route('/contacto/insertarSinProvincia', name: 'insertar_sin_provincia_contacto')]
    public function insertarSinProvincia(ManagerRegistry $doctrine): Response{
        $entityManager = $doctrine -> getManager();
        $repositorio = $doctrine->getRepository(Provincia::class);
        $provincia = $repositorio->findOneBy(["nombre" => "Alicante"]);

        $contacto = new Contacto();
        $contacto->setNombre("Inserción de prueba sin provincia");
        $contacto->setTelefono("900220022");
        $contacto->setEmail("insercion.de.prueba.sin.provincia@contacto.es");
        $contacto->setProvincia($provincia);

        $entityManager->persist($contacto);

        $entityManager->flush();
        return $this->render("ficha_contacto.html.twig", [
            "contacto"=>$contacto
        ]);
    }

    #[Route('/contacto/{codigo}', name: 'ficha_contacto')]
    public function ficha(ManagerRegistry $doctrine, SessionInterface $session,Request $request,$codigo): Response
    {
        $user = $this->getUser();
        if($user){ //Compruebo que el usuario haya iniciado sesion
            $repositorio = $doctrine->getRepository(Contacto::class);
            $contacto = $repositorio->find($codigo);    
            return $this->render("ficha_contacto.html.twig", [
                'contacto' => $contacto, "codigo" => $codigo
            ]);
        }else{
            //Me guardo la ruta para cuando haga log in me redireccione a esta página
            $currentRoute = $request->getUri();
            $currentRoute = parse_url($currentRoute);
            $session->set('url', $currentRoute["path"]);
            return $this->redirectToRoute("app_login");
        }
        
    }

    #[Route('/contacto/buscar/{texto}', name: 'buscar_contacto')]
    public function buscar(ManagerRegistry $doctrine, $texto): Response{
        $repositorio = $doctrine ->getRepository(Contacto::class);
        $contactos = $repositorio->findByName($texto);
        
        return $this->render("lista_contactos.html.twig", [
            'contactos' => $contactos, "texto" => $texto
        ]);
    }

    
}
