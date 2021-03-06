<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use PUGX\AutocompleterBundle\Form\Type\AutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ObservationType extends AbstractType
{
    private $apikey;

    public function __construct($apikey)
    {
        $this->apikey = $apikey;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('adresse', Type\TextType::class, array(
                'attr' => array(
                    'placeholder' => '47 Place du Marché Saint-Honoré'
                )
            ))
            ->add('date', Type\DateType::class, array(
                'years' => array(
                    date("Y"),
                    date("Y") - 1
                ),
            ))
            ->add('bird', AutocompleteType::class, array(
                'attr' => array(
                    'placeholder' => 'Aigle impérial'
                ),
                'class' => 'AppBundle:Bird'
            ))
            ->add('individuals', Type\IntegerType::class, array(
                'attr' => array(
                    'placeholder' => '1'
                )
            ))
            ->add('image', FileType::class, array(
                'required' => false
            ))
            ->add('commentaire', Type\TextareaType::class)
        ;

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();

                $data = $event->getForm()->getData();

                $adresse = $data->getAdresse();
                $geocode = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($adresse).'&key='.$this->apikey);
                $output = json_decode($geocode);

                try {
                    $latitude = $output->results[0]->geometry->location->lat;
                    $longitude = $output->results[0]->geometry->location->lng;
                    $adresse = $output->results[0]->formatted_address;

                    $data->setLatitude($latitude);
                    $data->setLongitude($longitude);
                    $data->setAdresse($adresse);
                } catch(\Throwable $e) {
                    die('Il y a une erreur dans l\'adresse de votre observation, veuillez réessayer.');
                }
            }
        );
    }
}