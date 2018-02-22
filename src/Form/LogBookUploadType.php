<?php

namespace App\Form;

use App\Entity\LogBookUpload;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('logFile', FileType::class, array('label' => 'logFile (DEBUG file)'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookUpload::class,
            // uncomment if you want to bind to a class
            //'data_class' => LogBookUpload::class,
        ]);
    }
}
