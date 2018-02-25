<?php

namespace App\Form;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookUpload;
use App\Repository\LogBookSetupRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('logFile', FileType::class, array('label' => 'logFile (DEBUG file)'))
//            ->add('setupName',  ChoiceType::class , $this->buildFormType())
//            ->add('setupId', FileType::class, array('label' => 'logFile (DEBUG file)'))
//            ->add('cycleId', FileType::class, array('label' => 'logFile (DEBUG file)'))
        ;
        // in SomeOjectType
//        $builder->add('setups',LogBookSetup::class , array(
//            'class' => 'LogBookSetup',
//            'query_builder' => function ($repository) {
//                return $repository->createQueryBuilder('g')
//                    ->orderBy('g.id', 'ASC');
//            },
//        ));
        $builder->add('setup', EntityType::class, array(
            'class' => LogBookSetup::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.id', 'ASC');
            },
            'choice_label' => 'name',
            'placeholder' => '',
            'required' => false,
            'empty_data' => '',
            'label' => "Select Setup for this log",
            'label_attr' => array('class'=> 'chosen-select form-control'),
        ));
        $builder->add('cycle', EntityType::class, array(
            'class' => LogBookCycle::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.id', 'DESC');
            },
            'choice_label' => 'id',
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookUpload::class,

            // uncomment if you want to bind to a class
            //'data_class' => LogBookUpload::class,
        ]);

    }
    /**
     * @return array
     */
    protected function buildFormType(){
        //      add('type', ChoiceType::class , $this->buildFormType())->
        return  array(
            'required' => true,
            'choices' => LogBookSetup::class,
//            'choices' => LogBookSetup::,
//            'query_builder' => function(EntityRepository $er->) use ($setup) {
//                return $er->findAll();
//            },
//            'choice_label' => function($choice) {
//                return LogBookSetup::getTypeName($choice);
//            },
        );
    }
}
