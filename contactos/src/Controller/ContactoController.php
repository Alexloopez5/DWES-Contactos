<?php

namespace App\Controller;

use App\Entity\Contacto;
use Doctrine\Persistence\ManagerRegistry;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactoController extends AbstractController
{
    private $contactos = [
        1 => ["nombre" => "Juan Pérez", "telefono" => "524142432", "email" => "juanp@ieselcaminas.org"],
        2 => ["nombre" => "Ana López", "telefono" => "58958448", "email" => "anita@ieselcaminas.org"],
        5 => ["nombre" => "Mario Montero", "telefono" => "5326824", "email" => "mario.mont@ieselcaminas.org"],
        7 => ["nombre" => "Laura Martínez", "telefono" => "42898966", "email" => "lm2000@ieselcaminas.org"],
        9 => ["nombre" => "Nora Jover", "telefono" => "54565859", "email" => "norajover@ieselcaminas.org"]
    ];   

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
            try
            {
                $entityManager->remove($contacto);
                $entityManager->flush();
                return new Response("Contacto eliminado");
            }catch(\Exception $e){
                return new Response("Error eliminando objeto ". $e->getMessage());
            }
        }else{
            return $this->render("ficha_contacto.html.twig", ["contacto" => null]);
        }
    }

    #[Route('/contacto/{codigo}', name: 'ficha_contacto')]
    public function ficha(ManagerRegistry $doctrine, $codigo): Response
    {
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);    
        return $this->render("ficha_contacto.html.twig", [
            'contacto' => $contacto
        ]);
    }

    #[Route('/contacto/buscar/{texto}', name: 'buscar_contacto')]
    public function buscar($texto): Response{
        $resultados = array_filter($this->contactos,
            function ($contacto) use ($texto){
                return strpos($contacto["nombre"],$texto) !== false;
            }
        );
        
        return $this->render("lista_contactos.html.twig", [
            'contactos' => $resultados, 'texto' => $texto
        ]);
    }

}
